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
use App\Models\Admin;
use App\Models\NotificationTemplate;
use App\Repositories\NotificationRepository;

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

        $meeting = $this->repository->create([
            'slot_id' => $validated['slot_id'],
            'client_id' => $validated['client_id'],
            'meeting_name' => $validated['meeting_name'],
            'description' => $validated['description'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'project_id' => $validated['project_id'] ?? null,
            'jitsi_url' => config('services.jitsi.base_url') . '/meeting-' . uniqid(),
            'status' => 'Request Sent',
        ]);

        if (!empty($validated['employee_ids'])) {
            $meeting->employees()->sync($validated['employee_ids']);
        }

        $this->sendMeetingCreatedNotification($meeting);

        return response()->json(new MeetingResource($meeting->load('employees')), 201);
    }

    private function sendMeetingCreatedNotification(Meeting $meeting)
    {
        $admins = Admin::whereNotNull('device_token')->get();

        if ($admins->isEmpty()) {
            Log::warning('No admins with device tokens found for meeting creation notification.');
            return;
        }

        $template = NotificationTemplate::where('type', 'meeting_created')->first();
        if (!$template) {
            Log::error('Notification template "meeting_created" not found.');
            return;
        }

        $title = $template->title;
        $message = str_replace(
            ['{meeting_name}', '{client_name}'],
            [$meeting->meeting_name, $meeting->client->name],
            $template->message
        );

        foreach ($admins as $admin) {
            try {
                
                $dataPayload = [
                    'meeting_id' => $meeting->id,
                    'notification_type' => 'meeting_created',
                ];
                app(FirebaseService::class)->sendNotification($admin->device_token, $title, $message, $dataPayload);
                app(NotificationRepository::class)->createNotification($admin, $title, $message, $admin->device_token, 'meeting_created');
            } catch (\Exception $e) {
                Log::error('Error sending meeting creation notification: ' . $e->getMessage());
            }
        }
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

        $oldStatus = $meeting->status;

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

        if ($request->status && $request->status !== $oldStatus) {
            $this->sendMeetingStatusNotification($meeting);
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

            $dataPayload = [
                'meeting_id' => $meeting->id,
                'notification_type' => 'meeting_status_updated',
            ];
            $this->firebaseService->sendNotification($client->device_token, $title, $message, $dataPayload);

            app(\App\Repositories\NotificationRepository::class)->createNotification($client, $title, $message, $client->device_token, 'meeting_status_updated');

            Log::info('Meeting status notification sent successfully.', ['client_id' => $client->id, 'meeting_id' => $meeting->id]);
        } catch (\Exception $e) {
            Log::error('Error sending meeting status notification: ' . $e->getMessage());
        }
    }

    public function getClientMeetings(Request $request)
    {
        $user = auth()->user(); 

        $query = Meeting::with(['project', 'employees:id,name,image'])
            ->where('client_id', $user->id);

        if ($request->has('search') && $request->search !== null) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('meeting_name', 'LIKE', "%$search%")
                ->orWhereHas('project', function ($projectQuery) use ($search) {
                    $projectQuery->where('name', 'LIKE', "%$search%");
                });
            });
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('start_time', [$request->from_date, $request->to_date]);
        }

        $meetings = $query
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($meeting) {
                return [
                    'id' => $meeting->id,
                    'meeting_name' => $meeting->meeting_name,
                    'project_name' => $meeting->project?->name,
                    'start_time' => $meeting->start_time,
                    'end_time' => $meeting->end_time,
                    'status' => $meeting->status,

                    'employees' => $meeting->employees->map(function ($emp) {
                        return [
                            'id' => $emp->id,
                            'name' => $emp->name,
                            'image' => $emp->image ? asset('uploads/employees/' . $emp->image) : null,
                        ];
                    }),
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $meetings
        ]);
    }

    public function getMeetingSummary($id)
    {
        $user = auth()->user();

        $meeting = Meeting::with([
            'project:id,name',
            'logs',
            'client:id,name',
            'slot',
            'employees:id,name,image' 
        ])
            ->where('client_id', $user->id)
            ->find($id);

        if (!$meeting) {
            return response()->json(['status' => false, 'message' => 'Meeting not found'], 404);
        }

        $start = \Carbon\Carbon::parse($meeting->start_time);
        $end = \Carbon\Carbon::parse($meeting->end_time);
        $duration = $start->diffInMinutes($end);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $meeting->id,
                'meeting_name' => $meeting->meeting_name,
                'project_name' => $meeting->project?->name,
                'date' => $meeting->slot?->date,
                'time' => [
                    'start' => $meeting->start_time,
                    'end' => $meeting->end_time
                ],
                'duration_minutes' => $duration,
                'status' => $meeting->status,
                'meeting_platform' => 'Jitsi',
                'jitsi_url' => $meeting->jitsi_url,

                'notes' => $meeting->description ? explode("\n", trim($meeting->description)) : [],

                'employees' => $meeting->employees->map(function ($emp) {
                    return [
                        'id' => $emp->id,
                        'name' => $emp->name,
                        'image' => $emp->image ? asset('uploads/employees/' . $emp->image) : null,
                    ];
                }),

                'action_log' => $meeting->logs->map(function ($log) {
                    return [
                        'date' => $log->created_at->format('d M Y'),
                        'action' => $log->action,
                        'details' => $log->details
                    ];
                }),
            ]
        ]);
    }

    public function destroy($id)
    {
        $meeting = Meeting::find($id);

        if (!$meeting) {
            return response()->json([
                'status' => false,
                'message' => 'Meeting not found.',
            ], 404);
        }

        $meeting->delete();

        return response()->json([
            'status' => true,
            'message' => 'Meeting deleted successfully.',
        ]);
    }



}
