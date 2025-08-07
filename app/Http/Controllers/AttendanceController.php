<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\AchievementAttachment;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Repositories\AttendanceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends BaseController
{
    public function __construct(AttendanceRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }


    public function getSessions(Request $request)
    {
        $user = auth()->user();

        $sessions = AttendanceSession::whereHas('attendance', function ($query) use ($user) {
            $query->where('employee_id', $user->id);
        })->get();

        return response()->json(['status' => true, 'sessions' => $sessions]);
    }

    public function realTimeStatus()
    {
        $user = auth()->user();
        $today = now()->toDateString();
        $shiftHours = 8;

        $attendance = Attendance::where('employee_id', $user->id)
            ->where('date', $today)
            ->with('sessions')
            ->first();

        if (!$attendance) {
            return response()->json([
                'status' => true,
                'last_status' => 'Not Checked In',
                'total_hours' => 0,
                'remaining_hours' => $shiftHours,
                'shift_completion' => '0%',
                'message' => 'No attendance for today.'
            ]);
        }

        $totalMinutes = 0;
        foreach ($attendance->sessions as $session) {
            $checkIn = Carbon::parse($session->check_in_time);
            $checkOut = $session->check_out_time ? Carbon::parse($session->check_out_time) : now();
            $totalMinutes += $checkIn->diffInMinutes($checkOut);
        }

        $lastOpenSession = $attendance->sessions
            ->sortByDesc('id')
            ->first();

        $lastStatus = 'Checked_out';

        if ($lastOpenSession) {
            if ($lastOpenSession->pause_started_at) {
                $lastStatus = 'Paused';
            }elseif ($lastOpenSession->check_out_time&&$lastOpenSession->pause_started_at== null) {
                $lastStatus = 'Checked_out';
            } else {
                $isFirstSession = $attendance->sessions->where('id', '<', $lastOpenSession->id)->isEmpty();
                $lastStatus = $isFirstSession ? 'Checked_in' : 'Resumed';
            }
        }

        $totalHours = round($totalMinutes / 60, 2);
        $remainingHours = max(round($shiftHours - $totalHours, 2), 0);
        $shiftCompletion = round(($totalHours / $shiftHours) * 100, 2) . '%';

        return response()->json([
            'status' => true,
            'last_status' => $lastStatus,
            'total_hours' => $totalHours,
            'remaining_hours' => $remainingHours,
            'shift_completion' => $shiftCompletion,
            'message' => 'Real-time attendance data fetched successfully.'
        ]);
    }

    public function checkIn(Request $request)
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::firstOrCreate([
            'employee_id' => $user->id,
            'date' => $today,
        ]);

        $lastSession = $attendance->sessions()->latest()->first();

        if ($lastSession && $lastSession->check_out_time && !$attendance->sessions()->whereNull('check_out_time')->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'You have already checked out completely for today.',
            ]);
        }

        $attendance->sessions()
            ->whereNull('check_out_time')
            ->update(['check_out_time' => now()]);

        $ip = $request->ip();
        $isInOffice = $ip === env('OFFICE_IP');

        $session = $attendance->sessions()->create([
            'ip_address' => $ip,
            'check_in_time' => now(),
            'is_in_office' => $isInOffice,
        ]);


        return response()->json([
            'status' => true,
            'message' => 'Checked in successfully.',
            'session' => $session
        ]);
    }

    public function pause()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::where('employee_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json(['status' => false, 'message' => 'No attendance found for today.']);
        }

        $session = $attendance->sessions()
            ->whereNull('check_out_time')
            ->latest()
            ->first();

        if (!$session) {
            return response()->json(['status' => false, 'message' => 'No active session to pause.']);
        }

        $session->update([
            'check_out_time' => now(),
            'is_in_office' => false,
            'pause_started_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Session paused successfully.',
            'session' => $session
        ]);
    }

    public function resume(Request $request)
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::firstOrCreate([
            'employee_id' => $user->id,
            'date' => $today,
        ]);

        $attendance->sessions()
            ->whereNull('check_out_time')
            ->update(['check_out_time' => now()]);

        $ip = $request->ip();
        $isInOffice = $ip === env('OFFICE_IP');

        $newSession = $attendance->sessions()->create([
            'ip_address' => $ip,
            'check_in_time' => now(),
            'is_in_office' => $isInOffice,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Session resumed successfully.',
            'session' => $newSession
        ]);
    }


    public function checkOut(Request $request)
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::where('employee_id', $user->id)
            ->where('date', $today)
            ->with('sessions')
            ->first();

        if (!$attendance) {
            return response()->json(['status' => false, 'message' => 'No attendance found for today.']);
        }

        $session = $attendance->sessions()
            ->whereNull('check_out_time')
            ->latest()
            ->first();

        if (!$session) {
            return response()->json(['status' => false, 'message' => 'No active session to check out.']);
        }

        $session->update([
            'check_out_time' => now(),
            'is_in_office' => false,
        ]);

        $totalMinutes = $attendance->sessions->sum(function ($s) {
            if ($s->check_in_time && $s->check_out_time) {
                return Carbon::parse($s->check_in_time)->diffInMinutes(Carbon::parse($s->check_out_time));
            }
            return 0;
        });

        $attendance->update([
            'total_hours' => round($totalMinutes / 60, 2),
        ]);

        $achievement = null;
        if ($request->filled('achievement_description') || $request->filled('issues_notes') || $request->hasFile('attachments')) {
            $achievement = Achievement::create([
                'created_by' => $user->id,
                'achievement_description' => $request->input('achievement_description'),
                'issues_notes' => $request->input('issues_notes'),
                'attendance_id' => $attendance->id,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('achievement_attachments', 'public');

                    AchievementAttachment::create([
                        'achievement_id' => $achievement->id,
                        'file_path' => $path,
                    ]);
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Checked out successfully.',
            'total_hours' => $attendance->total_hours,
            'session' => $session,
            'achievement' => $achievement ? $achievement->load('attachments') : null,
        ]);
    }

}
