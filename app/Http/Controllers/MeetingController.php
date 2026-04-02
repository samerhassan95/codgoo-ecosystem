<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequest;
use App\Http\Resources\MeetingResource;
use App\Http\Resources\ProjectResource;
use App\Models\AvailableSlot;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
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

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $meetings = Meeting::with(['project'])->get();
            
            return response()->json([
                'status' => true,
                'message' => 'Meetings retrieved successfully.',
                'data' => $meetings
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve meetings.',
                'data' => null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $meeting = Meeting::with(['project'])->findOrFail($id);
            
            return response()->json([
                'status' => true,
                'message' => 'Meeting retrieved successfully.',
                'data' => $meeting
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Meeting not found.',
                'data' => null
            ], 404);
        }
    }


public function store(MeetingRequest $request)
{
    $user = auth()->user();

    // 1️⃣ Ensure authenticated user is a client
    if (!$user || $user instanceof \App\Models\Admin) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    $validated = $request->validated();

    // 2️⃣ Validate project belongs to authenticated client
    $project = Project::where('id', $validated['project_id'])
        ->where('client_id', $user->id)
        ->first();

    if (!$project) {
        return response()->json([
            'status' => false,
            'message' => 'Project not found or access denied'
        ], 404);
    }

    // 3️⃣ Validate task belongs to project (if task_id is provided)
    $taskId = $validated['task_id'] ?? null;
    if ($taskId) {
        $task = Task::where('id', $taskId)
            ->whereHas('milestone', function ($q) use ($project) {
                $q->where('project_id', $project->id);
            })
            ->first();

        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found or does not belong to this project'
            ], 404);
        }
    }
    

$slot = AvailableSlot::findOrFail($validated['slot_id']);


    // 🔒 4️⃣ Check if time slot is already booked
    $overlappingMeeting = Meeting::where('slot_id', $validated['slot_id'])
        ->where('date', $slot->date)
        ->where(function ($query) use ($validated) {
            $query->where(function ($q) use ($validated) {
                // New meeting starts during existing meeting
                $q->where('start_time', '<', $validated['end_time'])
                  ->where('end_time', '>', $validated['start_time']);
            });
        })
        ->exists();
        
        
        if ($overlappingMeeting) {
        return response()->json([
            'status' => false,
            'message' => 'This time slot is already booked. Please choose another time.'
        ], 409); // 409 Conflict
    }
    
    
    // 4️⃣ Create meeting
    $meeting = $this->repository->create([
        'slot_id'      => $validated['slot_id'],
        'client_id'    => $user->id,
        'meeting_name' => $validated['meeting_name'],
        'description'  => $validated['description'] ?? null,
        'date'         => $slot->date,
        'start_time'   => $validated['start_time'],
        'end_time'     => $validated['end_time'],
        'project_id'   => $project->id,
        'task_id'      => $taskId, // optional
        'jitsi_url'    => config('services.jitsi.base_url') . '/meeting-' . uniqid(),
        'status'       => 'Request Sent',
    ]);

    // 5️⃣ Attach employees (optional)
    if (!empty($validated['employee_ids'])) {
        $meeting->employees()->sync($validated['employee_ids']);
    }

    // 6️⃣ Notify relevant users
    $this->sendMeetingCreatedNotification($meeting);

    return response()->json([
        'status'  => true,
        'message' => 'Meeting request sent successfully',
        'data'    => new MeetingResource($meeting->load('employees')),
    ], 201);
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

    // 🔒 Only allow updates if current status is 'Request Sent'
    if ($meeting->status !== 'Request Sent') {
        return response()->json([
            'status' => false,
            'message' => 'Cannot update meeting. Only meetings with status "Request Sent" can be updated.',
        ], 403);
    }

    $oldStatus = $meeting->status;

    // ✅ Validate provided status if any
    $allowedStatuses = ['Request Sent', 'Confirmed', 'Completed', 'Canceled'];
    $status = $request->status;
    if ($status && !in_array($status, $allowedStatuses)) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid status value.',
        ], 422);
    }

    // 🔹 Update fields
    $meeting->update([
        'slot_id'      => $request->slot_id ?? $meeting->slot_id,
        'meeting_name' => $request->meeting_name ?? $meeting->meeting_name,
        'description'  => $request->description ?? $meeting->description,
        'start_time'   => $request->start_time ?? $meeting->start_time,
        'end_time'     => $request->end_time ?? $meeting->end_time,
        'project_id'   => $request->project_id ?? $meeting->project_id,
        'task_id'      => $request->task_id ?? $meeting->task_id,
        'jitsi_url'    => $request->jitsi_url ?? $meeting->jitsi_url,
        'status'       => $status ?? $meeting->status,
    ]);

    // 🔔 Notify only if status changed
    if ($status && $status !== $oldStatus) {
        $this->sendMeetingStatusNotification($meeting);
    }

    return response()->json([
        'status'  => true,
        'message' => 'Meeting updated successfully.',
        'data'    => new MeetingResource($meeting->load('employees')),
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

    // 🔍 Optional search filter
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('meeting_name', 'LIKE', "%$search%")
              ->orWhereHas('project', function ($projectQuery) use ($search) {
                  $projectQuery->where('name', 'LIKE', "%$search%");
              });
        });
    }

    // ✅ Filter by status
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // ✅ Filter by date range
    if ($request->filled('from_date') && $request->filled('to_date')) {
        $query->whereBetween('start_time', [$request->from_date, $request->to_date]);
    }

    // 🔹 NEW: Filter by task_id
    if ($request->filled('task_id')) {
        $query->where('task_id', $request->task_id);
    }

    $meetings = $query
        ->orderByDesc('updated_at')
        ->get()
        ->map(function ($meeting) {

            $canAddNotes = $meeting->status === 'completed';

            return [
                'id' => $meeting->id,
                'meeting_name' => $meeting->meeting_name,
                'description' => $meeting->description,
                'project_name' => $meeting->project?->name,
                'task_id' => $meeting->task_id, // include task_id for frontend reference
                'start_time' => $meeting->start_time,
                'end_time' => $meeting->end_time,
                'status' => $meeting->status ?: 'request_sent',
                'date' => $meeting->date,
                // Notes info
                'notes' => $meeting->notes,
                'can_add_notes' => $canAddNotes,
                'has_notes' => !empty($meeting->notes),
                

                // Optional employee info
                'team' => $meeting->employees->map(function ($emp) {
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




public function saveNotes(Request $request, Meeting $meeting)
{
    $user = auth()->user();

    // Ensure meeting belongs to this client
    if ($meeting->client_id !== $user->id) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    // Only completed meetings
    if ($meeting->status !== 'Completed') {
        return response()->json([
            'status' => false,
            'message' => 'Notes can only be added to completed meetings.'
        ], 422);
    }

    $validated = $request->validate([
        'notes' => 'required|string|min:3'
    ]);

    $meeting->update([
        'notes' => $validated['notes']
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Meeting notes saved successfully.',
        'data' => [
            'meeting_id' => $meeting->id,
            'notes' => $meeting->notes
        ]
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


public function cancelMeeting(Request $request, $id)
{
    $user = auth()->user();
    $meeting = Meeting::with('employees', 'client')->find($id);

    if (!$meeting) {
        return response()->json([
            'status' => false,
            'message' => 'Meeting not found.',
        ], 404);
    }

    // Only meeting owner or admin can cancel
    if ($meeting->client_id !== $user->id && !($user instanceof \App\Models\Admin)) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized to cancel this meeting.',
        ], 403);
    }

    // Prevent canceling already canceled or completed meetings
    if (in_array(strtolower($meeting->status), ['canceled', 'completed'])) {
        return response()->json([
            'status' => false,
            'message' => "Cannot cancel a meeting that is already {$meeting->status}.",
        ], 422);
    }

    // Update status to canceled
    $meeting->update(['status' => 'Canceled']);

    // Notify client
    if ($meeting->client && $meeting->client->device_token) {
        try {
            $template = NotificationTemplate::where('type', 'meeting_canceled')->first();
            if ($template) {
                $title = $template->title;
                $message = str_replace(
                    ['{meeting_name}'],
                    [$meeting->meeting_name],
                    $template->message
                );

                $this->firebaseService->sendNotification(
                    $meeting->client->device_token,
                    $title,
                    $message,
                    ['meeting_id' => $meeting->id, 'notification_type' => 'meeting_canceled']
                );

                app(\App\Repositories\NotificationRepository::class)
                    ->createNotification($meeting->client, $title, $message, $meeting->client->device_token, 'meeting_canceled');
            }
        } catch (\Exception $e) {
            Log::error('Error sending meeting cancel notification: ' . $e->getMessage());
        }
    }

    // Optionally notify employees
    foreach ($meeting->employees as $employee) {
        if ($employee->device_token) {
            try {
                $template = NotificationTemplate::where('type', 'meeting_canceled')->first();
                if ($template) {
                    $title = $template->title;
                    $message = str_replace(
                        ['{meeting_name}'],
                        [$meeting->meeting_name],
                        $template->message
                    );

                    $this->firebaseService->sendNotification(
                        $employee->device_token,
                        $title,
                        $message,
                        ['meeting_id' => $meeting->id, 'notification_type' => 'meeting_canceled']
                    );

                    app(\App\Repositories\NotificationRepository::class)
                        ->createNotification($employee, $title, $message, $employee->device_token, 'meeting_canceled');
                }
            } catch (\Exception $e) {
                Log::error('Error sending meeting cancel notification to employee: ' . $e->getMessage());
            }
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'Meeting canceled successfully.',
        'data' => new MeetingResource($meeting->fresh()),
    ]);
}



public function joinMeeting(Request $request, $id)
{
    $user = auth()->user();
    $meeting = Meeting::with(['employees', 'client', 'project'])->find($id);

    if (!$meeting) {
        return response()->json([
            'status' => false,
            'message' => 'Meeting not found.',
        ], 404);
    }

    // Authorization: only client, admin, or assigned employee can join
    $isEmployee = $meeting->employees->contains('id', $user->id);
    if ($meeting->client_id !== $user->id && !($user instanceof \App\Models\Admin) && !$isEmployee) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized to join this meeting.',
        ], 403);
    }

    // Optionally, prevent joining canceled meetings
    if (strtolower($meeting->status) === 'canceled') {
        return response()->json([
            'status' => false,
            'message' => 'This meeting has been canceled.',
        ], 422);
    }

    // Optionally log join action
    $meeting->logs()->create([
        'user_id' => $user->id,
        'action' => 'joined_meeting',
        'details' => $user->name . ' joined the meeting.',
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Meeting joined successfully.',
        'data' => [
            'meeting_id' => $meeting->id,
            'meeting_name' => $meeting->meeting_name,
            'jitsi_url' => $meeting->jitsi_url,
            'project_name' => $meeting->project?->name,
            'start_time' => $meeting->start_time,
            'end_time' => $meeting->end_time,
            'status' => $meeting->status,
        ],
    ]);
}

}
