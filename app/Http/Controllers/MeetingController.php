<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequest;
use App\Http\Resources\MeetingResource;
use App\Http\Resources\ProjectResource;
use App\Models\AvailableSlot;
use App\Models\Meeting;
use App\Repositories\MeetingRepositoryInterface;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MeetingController extends Controller
{
    private $repository;
    private $firebaseService;

    public function __construct(MeetingRepositoryInterface $repository, FirebaseService $firebaseService)
    {
        $this->repository = $repository;
        $this->firebaseService = $firebaseService;
    }

    public function store(MeetingRequest $request)
    {
        $validated = $request->validated();

        $duration = $validated['duration'] ?? 60;

        $slot = AvailableSlot::findOrFail($validated['slot_id']);
        $requestedStart = Carbon::parse($validated['start_time']);
        $requestedEnd = $requestedStart->copy()->addMinutes($duration);

        $existingMeetings = $slot->meetings()
            ->where(function ($query) use ($requestedStart, $requestedEnd) {
                $query->where('start_time', '<', $requestedEnd->toTimeString())
                      ->where('end_time', '>', $requestedStart->toTimeString());
            })
            ->exists();

        if ($existingMeetings) {
            return response()->json([
                'status' => false,
                'message' => 'This time slot is already occupied.',
            ], 400);
        }

        $jitsiRoom = 'meeting-' . uniqid();
        $jitsiUrl = config('services.jitsi.base_url') . '/' . $jitsiRoom;

        $meeting = $this->repository->create([
            'slot_id' => $validated['slot_id'],
            'client_id' => $validated['client_id'],
            'meeting_name' => $validated['meeting_name'],
            'description' => $validated['description'] ?? null,
            'start_time' => $requestedStart->toTimeString(),
            'end_time' => $requestedEnd->toTimeString(),
            'project_id' => $validated['project_id'] ?? null,
            'jitsi_url' => $jitsiUrl,
            'status' => 'Request Sent',
        ]);

        $slot->meetings()->update([
            'status' => true
        ]);

        return response()->json(new MeetingResource($meeting), 201);
    }


    public function getMeetingsForClient(Request $request)
    {
        $client = $request->user();

        $meetings = Meeting::where('client_id', $client->id)
            ->join('available_slots', 'meetings.slot_id', '=', 'available_slots.id')
            ->orderByDesc('available_slots.date')
            ->select('meetings.*') // Ensure only meeting fields are retrieved
            ->get();

        return MeetingResource::collection($meetings);
    }



    public function filterMeetingsByStatus(Request $request)
    {
        $client = $request->user();

        $statusMapping = [
            0 => 'all',
            1 => 'Request Sent',
            2 => 'Confirmed',
            3 => 'Completed',
            4 => 'Canceled',
        ];

        $status = $request->query('status');

        $meetingsQuery = Meeting::where('client_id', $client->id)
            ->join('available_slots', 'meetings.slot_id', '=', 'available_slots.id')
            ->orderByDesc('available_slots.date')
            ->select('meetings.*');

        if ($status && isset($statusMapping[$status]) && $status != 0) {
            $meetingsQuery->where('status', $statusMapping[$status]);
        }

        $meetings = $meetingsQuery->get();

        return MeetingResource::collection($meetings);
    }



    public function getMeetingById($id, Request $request)
    {
        $user = $request->user();
        $meeting = Meeting::with('project')->find($id);

        if (!$meeting) {
            return response()->json([
                'status' => false,
                'message' => 'Meeting not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'meeting' => new MeetingResource($meeting),
                'project' => new ProjectResource($meeting->project),
            ],
        ]);
    }


    public function getMeetingsWithProject(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $meetings = $this->repository->getMeetingsWithProject($perPage);

        $filteredMeetings = $meetings->map(function ($meeting) {
            return [
                'meeting_id' => $meeting->id,
                'meeting_name' => $meeting->meeting_name,
                'meeting_date' => $meeting->slot ? $meeting->slot->date : null,
                'name' => $meeting->project ? $meeting->project->name : null,
                'status' => $meeting->status,
                'start_time' => $meeting->start_time,
                'end_time' => $meeting->end_time,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $filteredMeetings->values(),
            'total' => $meetings->total(),
            'per_page' => $meetings->perPage(),
            'from' => $meetings->firstItem(),
            'to' => $meetings->lastItem(),
            'count' => $meetings->count(),

        ]);
    }

    public function update(MeetingRequest $request, $id)
    {
        $meeting = Meeting::find($id);

        if (!$meeting) {
            return response()->json([
                'status' => false,
                'message' => 'Meeting not found.',
            ], 404);
        }

        $oldStatus = $meeting->status; // حفظ الحالة السابقة

        $meeting->update([
            'slot_id' => $request->slot_id ?? $meeting->slot_id,
            'meeting_name' => $request->meeting_name ?? $meeting->meeting_name,
            'description' => $request->description ?? $meeting->description,
            'start_time' => $request->start_time ?? $meeting->start_time,
            'end_time' => $request->end_time ?? $meeting->end_time,
            'project_id' => $request->project_id ?? $meeting->project_id,
            'jitsi_url' => $request->jitsi_url ?? $meeting->jitsi_url,
            'status' => $request->status ?? $meeting->status,
        ]);

        // إرسال إشعار فقط إذا تغيرت الحالة
        if ($request->status && $request->status !== $oldStatus) {
            $this->sendMeetingStatusNotification($meeting);
        }

        // إذا تغيرت الحالة إلى "ملغى"، نقوم بتحديث حالة المشروع إلى "مرفوض"
        if ($request->status === 'Canceled' && $meeting->project) {
            $meeting->project->update(['status' => 'reject']);
        }

        return response()->json([
            'status' => true,
            'message' => 'Meeting updated successfully.',
            'data' => new MeetingResource($meeting),
        ]);
    }

    private function sendMeetingStatusNotification(Meeting $meeting)
    {
        $client = $meeting->client;

        if (!$client || !$client->device_token) {
            Log::warning('Client not found or has no device token for meeting status notification.', [
                'meeting_id' => $meeting->id,
                'client_id' => $client ? $client->id : null
            ]);
            return;
        }

        $template = \App\Models\NotificationTemplate::where('type', 'meeting_status_updated')->first();
        if (!$template) {
            Log::error('Notification template "meeting_status_updated" not found.');
            return;
        }

        $title = $template->title;
        $message = str_replace(
            ['{meeting_name}', '{status}'],
            [$meeting->meeting_name, ucfirst($meeting->status)],
            $template->message
        );

        try {
            $this->firebaseService->sendNotification($client->device_token, $title, $message);

            app(\App\Repositories\NotificationRepository::class)->createNotification($client, $title, $message, $client->device_token);

            Log::info('Meeting status notification sent successfully.', ['client_id' => $client->id, 'meeting_id' => $meeting->id]);
        } catch (\Exception $e) {
            Log::error('Error sending meeting status notification: ' . $e->getMessage());
        }
    }



}
