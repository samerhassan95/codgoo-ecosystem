<?php

namespace App\Http\Controllers;

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

    // public function checkIn(Request $request)
    // {
    //     $user = auth()->user();
    //     $today = now()->toDateString();

    //     $attendance = Attendance::firstOrCreate([
    //         'employee_id' => $user->id,
    //         'date' => $today,
    //     ]);

    //     $session = $attendance->sessions()->create([
    //         'ip_address' => $request->ip(),
    //         'check_in_time' => now(),
    //         'is_in_office' => true,
    //     ]);

    //     return response()->json(['status' => true, 'session' => $session]);
    // }

    // public function pause(Request $request, $sessionId)
    // {
    //     $session = AttendanceSession::findOrFail($sessionId);

    //     if ($session->pause_started_at) {
    //         return response()->json(['message' => 'Already paused']);
    //     }

    //     $session->update([
    //         'pause_started_at' => now(),
    //     ]);

    //     return response()->json(['status' => true, 'message' => 'Paused']);
    // }

    // public function resume(Request $request, $sessionId)
    // {
    //     $session = AttendanceSession::findOrFail($sessionId);

    //     if (!$session->pause_started_at) {
    //         return response()->json(['message' => 'Not paused']);
    //     }

    //     $pausedDuration = now()->diffInMinutes($session->pause_started_at);

    //     $session->update([
    //         'pause_started_at' => null,
    //         'total_pause_minutes' => $session->total_pause_minutes + $pausedDuration,
    //     ]);

    //     return response()->json(['status' => true, 'message' => 'Resumed']);
    // }

    // public function checkOut(Request $request, $sessionId)
    // {
    //     $session = AttendanceSession::findOrFail($sessionId);

    //     if (!$session->check_out_time) {
    //         $checkIn = $session->check_in_time;
    //         $checkOut = now();

    //         $duration = $checkOut->diffInMinutes($checkIn) - $session->total_pause_minutes;

    //         $session->update([
    //             'check_out_time' => $checkOut,
    //         ]);

    //         $attendance = $session->attendance;
    //         $attendance->total_hours += round($duration / 60, 2);
    //         $attendance->save();
    //     }

    //     return response()->json(['status' => true, 'message' => 'Checked out']);
    // }

    // public function checkIn(Request $request)
    // {
    //     $user = auth()->user();
    //     $today = now()->toDateString();

    //     $attendance = Attendance::firstOrCreate([
    //         'employee_id' => $user->id,
    //         'date' => $today,
    //     ]);

    //     $attendance->sessions()->whereNull('check_out_time')->update([
    //         'check_out_time' => now(),
    //     ]);

    //     $session = $attendance->sessions()->create([
    //         'ip_address' => $request->ip(),
    //         'check_in_time' => now(),
    //         'is_in_office' => true,
    //     ]);

    //     return response()->json(['status' => true, 'session' => $session]);
    // }


    // public function pauseOrCheckOut(Request $request)
    // {
    //     $user = auth()->user();
    //     $today = now()->toDateString();

    //     $attendance = Attendance::where('employee_id', $user->id)->where('date', $today)->first();

    //     if (!$attendance) {
    //         return response()->json(['status' => false, 'message' => 'No active attendance.']);
    //     }

    //     $openSession = $attendance->sessions()->whereNull('check_out_time')->latest()->first();

    //     if ($openSession) {
    //         $openSession->update([
    //             'check_out_time' => now(),
    //         ]);
    //     }

    //     $totalMinutes = $attendance->sessions()
    //         ->whereNotNull('check_out_time')
    //         ->get()
    //         ->sum(function ($session) {
    //             return now()->parse($session->check_out_time)->diffInMinutes($session->check_in_time);
    //         });

    //     $attendance->update([
    //         'total_hours' => round($totalMinutes / 60, 2),
    //     ]);

    //     return response()->json(['status' => true, 'message' => 'Session ended']);
    // }

//     public function checkIn(Request $request)
// {
//     $user = auth()->user();
//     $today = now()->toDateString();

//     $attendance = Attendance::firstOrCreate([
//         'employee_id' => $user->id,
//         'date' => $today,
//     ]);

//     $attendance->sessions()->create([
//         'ip_address' => $request->ip(),
//         'check_in_time' => now(),
//         'is_in_office' => true,
//     ]);

//     return response()->json(['status' => true, 'message' => 'Checked in successfully']);
// }

// public function pause($sessionId)
// {
//     $session = AttendanceSession::findOrFail($sessionId);

//     if ($session->check_out_time !== null) {
//         return response()->json(['status' => false, 'message' => 'Session already ended']);
//     }

//     $session->update(['check_out_time' => now()]);

//     return response()->json(['status' => true, 'message' => 'Paused successfully']);
// }

// public function resume(Request $request)
// {
//     $user = auth()->user();
//     $today = now()->toDateString();

//     $attendance = Attendance::firstOrCreate([
//         'employee_id' => $user->id,
//         'date' => $today,
//     ]);

//     $session = $attendance->sessions()->create([
//         'ip_address' => $request->ip(),
//         'check_in_time' => now(),
//         'is_in_office' => true,
//     ]);

//     return response()->json(['status' => true, 'message' => 'Resumed successfully']);
// }

// public function checkOut($sessionId)
// {
//     $session = AttendanceSession::findOrFail($sessionId);

//     if ($session->check_out_time !== null) {
//         return response()->json(['status' => false, 'message' => 'Session already ended']);
//     }

//     $session->update(['check_out_time' => now()]);

//     $attendance = $session->attendance;
//     $totalMinutes = $attendance->sessions()->whereNotNull('check_out_time')->get()->sum(function ($s) {
//         return \Carbon\Carbon::parse($s->check_out_time)->diffInMinutes($s->check_in_time);
//     });

//     $attendance->update([
//         'total_hours' => round($totalMinutes / 60, 2),
//     ]);

//     return response()->json(['status' => true, 'message' => 'Checked out successfully']);
// }


    public function getSessions(Request $request)
    {
        $user = auth()->user();

        $sessions = AttendanceSession::whereHas('attendance', function ($query) use ($user) {
            $query->where('employee_id', $user->id);
        })->get();

        return response()->json(['status' => true, 'sessions' => $sessions]);
    }


    // public function checkIn(Request $request)
    // {
    //     $user = auth()->user();
    //     $today = now()->toDateString();

    //     $attendance = Attendance::firstOrCreate([
    //         'employee_id' => $user->id,
    //         'date' => $today,
    //     ]);

    //     $existingSession = $attendance->sessions()->whereNull('check_out_time')->first();
    //     if ($existingSession) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'You already have an open session. Please pause or check-out first.',
    //         ]);
    //     }

    //     $session = $attendance->sessions()->create([
    //         'ip_address' => $request->ip(),
    //         'check_in_time' => now(),
    //         'is_in_office' => true,
    //     ]);

    //     return response()->json(['status' => true, 'session' => $session, 'message' => 'Check-in done.']);
    // }
    // public function pause($sessionId)
    // {
    //     $session = AttendanceSession::findOrFail($sessionId);

    //     if ($session->check_out_time) {
    //         return response()->json(['status' => false, 'message' => 'Session already ended.']);
    //     }

    //     $session->update(['check_out_time' => now()]);

    //     return response()->json(['status' => true, 'message' => 'Paused session.']);
    // }

    // public function resume(Request $request)
    // {
    //     $user = auth()->user();
    //     $today = now()->toDateString();

    //     $attendance = Attendance::firstOrCreate([
    //         'employee_id' => $user->id,
    //         'date' => $today,
    //     ]);

    //     $session = $attendance->sessions()->create([
    //         'ip_address' => $request->ip(),
    //         'check_in_time' => now(),
    //         'is_in_office' => true,
    //     ]);

    //     return response()->json(['status' => true, 'session' => $session, 'message' => 'Resumed session.']);
    // }

    // public function checkOut($sessionId)
    // {
    //     $session = AttendanceSession::findOrFail($sessionId);

    //     if ($session->check_out_time) {
    //         return response()->json(['status' => false, 'message' => 'Session already ended.']);
    //     }

    //     $session->update(['check_out_time' => now()]);

    //     $attendance = $session->attendance;
    //     $totalMinutes = $attendance->sessions()
    //         ->whereNotNull('check_out_time')
    //         ->get()
    //         ->sum(function ($s) {
    //             return Carbon::parse($s->check_in_time)->diffInMinutes($s->check_out_time);
    //         });

    //     $attendance->update([
    //         'total_hours' => round($totalMinutes / 60, 2),
    //     ]);

    //     return response()->json(['status' => true, 'message' => 'Checked out.']);
    // }


    public function realTimeStatus()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::where('employee_id', $user->id)->where('date', $today)->first();

        if (!$attendance) {
            return response()->json(['status' => true, 'total_hours' => 0, 'message' => 'No attendance for today.']);
        }

        $totalMinutes = 0;

        foreach ($attendance->sessions as $session) {
            $checkOut = $session->check_out_time ?? now();
            $totalMinutes += Carbon::parse($session->check_in_time)->diffInMinutes(Carbon::parse($checkOut));
        }

        return response()->json([
            'status' => true,
            'total_hours' => round($totalMinutes / 60, 2),
            'message' => 'Real-time attendance hours.',
        ]);
    }


       public function checkIn(Request $request)
{
    $user = auth()->user();
    $today = now()->toDateString();

    // إنشاء attendance لليوم الحالي إن لم يوجد
    $attendance = Attendance::firstOrCreate([
        'employee_id' => $user->id,
        'date' => $today,
    ]);

    // التحقق إذا كانت آخر جلسة اليوم تم تسجيل خروجها (check_out)
    $lastSession = $attendance->sessions()->latest()->first();

    if ($lastSession && $lastSession->check_out_time && !$attendance->sessions()->whereNull('check_out_time')->exists()) {
        // يعني الموظف خلّص كل الجلسات ومفيش جلسة مفتوحة
        return response()->json([
            'status' => false,
            'message' => 'You have already checked out completely for today.',
        ]);
    }

    // إنهاء أي جلسة مفتوحة (لو موجودة)
    $attendance->sessions()
        ->whereNull('check_out_time')
        ->update(['check_out_time' => now()]);

    // بدء جلسة جديدة
    $session = $attendance->sessions()->create([
        'ip_address' => $request->ip(),
        'check_in_time' => now(),
        'is_in_office' => true,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Checked in successfully.',
        'session' => $session
    ]);
}


    public function pause($sessionId)
    {
        $session = AttendanceSession::findOrFail($sessionId);

        if ($session->check_out_time) {
            return response()->json(['status' => false, 'message' => 'Session already ended.']);
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

   public function resume()
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

        $newSession = $attendance->sessions()->create([
            'ip_address' => request()->ip(),
            'check_in_time' => now(),
            'is_in_office' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Session resumed successfully.',
            'session' => $newSession
        ]);
    }

    public function checkOut($sessionId)
    {
        $session = AttendanceSession::findOrFail($sessionId);

        if ($session->check_out_time) {
            return response()->json(['status' => false, 'message' => 'Already checked out.']);
        }

        $session->update([
            'check_out_time' => now(),
            'is_in_office' => false,
        ]);

        $attendance = $session->attendance;
        $totalMinutes = 0;

        foreach ($attendance->sessions as $s) {
            if ($s->check_out_time && $s->check_in_time) {
                $totalMinutes += Carbon::parse($s->check_in_time)->diffInMinutes(Carbon::parse($s->check_out_time));
            }
        }

        $attendance->update([
            'total_hours' => round($totalMinutes / 60, 2)
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Checked out successfully.',
            'total_hours' => $attendance->total_hours,
            'session' => $session
        ]);
    }


}