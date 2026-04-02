<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SliderResource;
use App\Models\Admin;
use App\Models\Product;
use App\Models\AvailableSlot;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Milestone;
use App\Models\Meeting;
use App\Models\NotificationTemplate;
use App\Models\Project;
use App\Models\Task;
use App\Repositories\NotificationRepository;
use App\Repositories\ProjectRepositoryInterface;
use App\Repositories\SliderRepositoryInterface;
use App\Services\FirebaseService;
use App\Services\ImageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request as HttpRequest;

class ProjectController extends BaseController
{
    private $repository;
    private $sliderRepository;

    public function __construct(ProjectRepositoryInterface $repository, SliderRepositoryInterface $sliderRepository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
        $this->sliderRepository = $sliderRepository;
    }


// public function store(Request $request)
// {
//     $validatedData = $request->validate([
//         'product_id' => 'required|exists:products,id',
//         'name' => 'required|string|max:255',
//         'description' => 'nullable|string',
//         'category_id' => 'nullable|exists:categories,id',

//         'addons' => 'array',
//         'addons.*' => 'exists:addons,id',

//         'attachments.*' => 'file|max:10240',

//         // ⏰ Meeting time (TIME only)
//         'slot_id'    => 'required|exists:available_slots,id',
//         'start_time' => 'required|date_format:H:i',
//         'end_time'   => 'required|date_format:H:i|after:start_time',
//     ]);

//     $user = auth()->user();

//     if (!$user instanceof \App\Models\Client) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Only clients can create projects.',
//         ], 403);
//     }

//     // Handle addons & meeting times
//     $addons = $validatedData['addons'] ?? [];
//     $meetingStart = $validatedData['start_time'];
//     $meetingEnd   = $validatedData['end_time'];

//     unset(
//         $validatedData['addons'],
//         $validatedData['attachments'],
//         $validatedData['start_time'],
//         $validatedData['end_time'],
//         $validatedData['slot_id']
//     );

//     // Create project
//     $validatedData['client_id'] = $user->id;

//     $product = Product::findOrFail($validatedData['product_id']);
//     $validatedData['price'] = $product->price;

//     $project = Project::create($validatedData);

//     // Upload attachments
//     if ($request->hasFile('attachments')) {
//         foreach ($request->file('attachments') as $file) {
//             $path = ImageService::upload($file, 'attachments');
//             $project->attachments()->create([
//                 'file_path' => $path,
//                 'uploaded_by_id' => $user->id,
//                 'uploaded_by_type' => get_class($user),
//             ]);
//         }
//     }

//     // Attach addons
//     if (!empty($addons)) {
//         $project->addons()->attach($addons);
//     }

//     // Create meeting
//     $slot = \App\Models\AvailableSlot::find($request->slot_id);

//     if (
//         $meetingStart < $slot->start_time ||
//         $meetingEnd > $slot->end_time
//     ) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Meeting time must be within the selected slot.'
//         ], 422);
//     }

//     $meeting = \App\Models\Meeting::create([
//         'slot_id'      => $slot->id,
//         'client_id'    => $user->id,
//         'project_id'   => $project->id,
//         'meeting_name' => $project->name . ' Meeting',
//         'description'  => $project->description,

//         // combine slot date + request time
//         'start_time' => $slot->date . ' ' . $meetingStart,
//         'end_time'   => $slot->date . ' ' . $meetingEnd,

//         'jitsi_url' => config('services.jitsi.base_url') . '/meeting-' . uniqid(),
//         'status'    => 'Request Sent',
//     ]);

//     // Optional notifications
//     $this->sendProjectCreatedNotification($project);
//     $this->sendMeetingCreatedNotification($meeting);

//     // Response
//     return response()->json([
//         'status' => true,
//         'message' => 'Project and meeting created successfully.',
//         'data' => [
//             'project' => new \App\Http\Resources\ProjectResource(
//                 $project->load(['attachments', 'addons'])
//             ),
//             'meeting' => new \App\Http\Resources\MeetingResource(
//                 $meeting->load('employees')
//             ),
//         ]
//     ], 201);
// }


public function store(Request $request)
{
    // 1️⃣ Validation
    $validatedData = $request->validate([
        'product_id'   => 'required|exists:products,id',
        'name'         => 'required|string|max:255',
        'description'  => 'nullable|string',
        'category_id'  => 'nullable|exists:categories,id',

        'addons'       => 'array',
        'addons.*'     => 'exists:addons,id',

        'attachments.*' => 'file|max:10240',

        // ⏰ Meeting time
        'slot_id'      => 'required|exists:available_slots,id',
        'start_time'   => 'required|date_format:H:i',
        'end_time'     => 'required|date_format:H:i|after:start_time',
    ]);

    // 2️⃣ Auth user check
    $user = auth()->user();

    if (!$user instanceof \App\Models\Client) {
        return response()->json([
            'status'  => false,
            'message' => 'Only clients can create projects.',
        ], 403);
    }

    // 3️⃣ Load Slot
    $slot = AvailableSlot::findOrFail($request->slot_id);

    // 4️⃣ Slot must be at least 2 days from now
    if (Carbon::parse($slot->date)->lt(now()->addDays(2))) {
        return response()->json([
            'status'  => false,
            'message' => 'Slot must be at least 2 days from now.',
        ], 400);
    }

    // 5️⃣ Check for overlapping meetings in the same slot
    $overlappingMeeting = Meeting::where('slot_id', $slot->id)
        ->where('date', $slot->date)
        ->where(function ($query) use ($validatedData) {
            $query->where(function ($q) use ($validatedData) {
                // New meeting overlaps with existing meeting
                $q->where('start_time', '<', $validatedData['end_time'])
                  ->where('end_time', '>', $validatedData['start_time']);
            });
        })
        ->exists();

    if ($overlappingMeeting) {
        return response()->json([
            'status'  => false,
            'message' => 'This time slot is already booked. Please choose another time.',
        ], 409);
    }

    // 6️⃣ Meeting time must be inside slot time
    if (
        $validatedData['start_time'] < $slot->start_time ||
        $validatedData['end_time']   > $slot->end_time
    ) {
        return response()->json([
            'status'  => false,
            'message' => 'Meeting time must be within the selected slot.',
        ], 422);
    }

    // 7️⃣ Prepare data
    $addons        = $validatedData['addons'] ?? [];
    $meetingStart = $validatedData['start_time'];
    $meetingEnd   = $validatedData['end_time'];

    unset(
        $validatedData['addons'],
        $validatedData['attachments'],
        $validatedData['start_time'],
        $validatedData['end_time'],
        $validatedData['slot_id']
    );

    // 8️⃣ Transaction (Atomic Operation)
    DB::beginTransaction();

    try {
        // Create Project
        $validatedData['client_id'] = $user->id;

        $product = Product::findOrFail($validatedData['product_id']);
        $validatedData['price'] = $product->price;

        $project = Project::create($validatedData);

        // Upload attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = ImageService::upload($file, 'attachments');
                $project->attachments()->create([
                    'file_path'       => $path,
                    'uploaded_by_id'  => $user->id,
                    'uploaded_by_type'=> get_class($user),
                ]);
            }
        }

        // Attach addons
        if (!empty($addons)) {
            $project->addons()->attach($addons);
        }

        // Create meeting
        $meeting = Meeting::create([
            'slot_id'      => $slot->id,
            'client_id'    => $user->id,
            'project_id'   => $project->id,
            'meeting_name' => $project->name . ' Meeting',
            'date'         => $slot->date,
            'description'  => $project->description,
            'start_time'   => $meetingStart,
            'end_time'     => $meetingEnd,
            'jitsi_url'    => config('services.jitsi.base_url') . '/meeting-' . uniqid(),
            'status'       => 'Request Sent',
        ]);

        DB::commit();

        // 9️⃣ Notifications (optional)
        $this->sendProjectCreatedNotification($project);
        $this->sendMeetingCreatedNotification($meeting);

        // 🔁 Response
        return response()->json([
            'status'  => true,
            'message' => 'Project and meeting created successfully.',
            'data' => [
                'project' => new \App\Http\Resources\ProjectResource(
                    $project->load(['attachments', 'addons'])
                ),
                'meeting' => new \App\Http\Resources\MeetingResource(
                    $meeting
                ),
            ]
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'status'  => false,
            'message' => 'Failed to create project.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}



    private function sendProjectCreatedNotification(Project $project)
    {
        $admins = Admin::whereNotNull('device_token')->get();

        if ($admins->isEmpty()) {
            Log::warning('No admins with device tokens found for project creation notification.');
            return;
        }

        $template = NotificationTemplate::where('type', 'project_created')->first();
        if (!$template) {
            Log::error('Notification template "project_created" not found.');
            return;
        }

        $title = $template->title;
        $message = str_replace(
            ['{project_name}', '{client_name}'],
            [$project->name, $project->client->name],
            $template->message
        );

        foreach ($admins as $admin) {
            try {

                $dataPayload = [
                    'project_id' => $project->id,
                    'notification_type' => 'project_created',
                ];
                app(FirebaseService::class)->sendNotification($admin->device_token, $title, $message, $dataPayload);
                // app(NotificationRepository::class)->createNotification($admin, $title, $message, $admin->device_token);
            } catch (\Exception $e) {
                Log::error('Error sending project creation notification: ' . $e->getMessage());
            }
        }
    }


    public function update(Request $request, $id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $validatedData = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:reject,completed,ongoing,requested',
            'addons' => 'array',
            'addons.*' => 'exists:addons,id',
            'attachments.*' => 'file|max:10240',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $user = auth()->user();

        if ($user instanceof \App\Models\Client && isset($validatedData['price'])) {
            return response()->json([
                'status' => false,
                'message' => 'Only Admin can update the price.',
            ], 403);
        }

        // Remove 'addons' and 'attachments' from the main project data
        $addons = $validatedData['addons'] ?? [];
        unset($validatedData['addons']);
        unset($validatedData['attachments']); 

        // Update the project
        $project->update($validatedData);


        // Update the project
        $project->update($validatedData);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = ImageService::upload($file, 'attachments');
                $project->attachments()->create([
                    'file_path' => $path,
                ]);
            }
        }

        // Update addons (Many-to-Many Relationship)
        $project->addons()->sync($addons);

        return response()->json(new ProjectResource($project->load(['attachments', 'addons'])), 200);
    }



    public function getStatusCounts()
    {
        $user = auth()->user();

        if (!$user || $user instanceof \App\Models\Admin) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $projects = Project::where('client_id', $user->id)->get();

        $statusCounts = [
            'requested' => 0,
            'ongoing' => 0,
            'completed' => 0,
            'reject' => 0,
        ];

        foreach ($projects as $project) {
            $status = $project->status;

            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }

        return response()->json([
            'status' => true,
            'data' => $statusCounts,
        ]);
    }

    public function filterProjectsByStatus($status)
    {
        $statusMapping = [
            0 => 'all',
            1 => 'completed',
            2 => 'ongoing',
            3 => 'requested',
            4 => 'reject',
        ];

        if (!array_key_exists($status, $statusMapping)) {
            return response()->json([
                'message' => 'Invalid status. Valid statuses are: 1 (completed), 2 (ongoing), 3 (requested), 4 (reject).'
            ], 400);
        }

        $statusString = $statusMapping[$status];

        $user = auth()->user();

        if (!$user || $user instanceof \App\Models\Admin) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $projects = Project::where('client_id', $user->id)
            ->with('milestones')
            ->get();

        if ($statusString === 'all') {
            return response()->json([
                'status' => true,
                'data' => ProjectResource::collection($projects),
            ]);
        }

        $filteredProjects = $projects->filter(function ($project) use ($statusString) {
            return $project->status === $statusString;
        });

        return response()->json([
            'status' => true,
            'data' => ProjectResource::collection($filteredProjects->values()),
        ]);
    }


public function getProjectDetails($projectId)
{
    $user = auth()->user();

    // Eager load attachments and activities (notes)
    // Note: Ensure your Project model has an 'activities' or 'notes' relationship
    $project = Project::where('id', $projectId)
        ->with(['milestones.tasks', 'addons', 'meetings', 'attachments']) 
        ->first();

    if (!$project) {
        return response()->json(['status' => false, 'message' => 'Project not found.'], 404);
    }

    $milestones = $project->milestones;
    $allTasks = $milestones->flatMap->tasks;

    // --- Define Status Options ---
    $allTaskStatuses = ['incomplete', 'doing', 'on hold', 'completed', 'canceled'];
    $allProjectStatuses = ['not_started', 'ongoing', 'completed', 'on_hold'];

    // --- 1. Numbers for Every Task Status ---
    $taskStatusCounts = collect($allTaskStatuses)->mapWithKeys(function ($status) use ($allTasks) {
        return [$status => $allTasks->where('status', $status)->count()];
    });

    // --- 2. Percentage for Every Progress Status ---
    // Calculated based on the number of milestones in each status
    $totalMilestones = $milestones->count();
    $progressStatusPercentages = collect($allProjectStatuses)->mapWithKeys(function ($status) use ($milestones, $totalMilestones) {
        $count = $milestones->where('status', $status)->count();
        $percentage = $totalMilestones > 0 ? round(($count / $totalMilestones) * 100, 2) : 0;
        return [$status => $percentage];
    });

    // --- 3. Activity Notes ---
    // Assuming you have a project notes or activity log. 
    // If it's a simple text field on the project table, use: $project->notes
    $activityNotes = $project->notes ?? "No activity notes available.";

    // Dates and Progress calculations
    $startDate = $milestones->min('start_date');
    $deadline = $milestones->max('end_date');
    $totalDays = $startDate && $deadline ? Carbon::parse($startDate)->diffInDays(Carbon::parse($deadline)) : null;
    $daysLeft = $deadline ? now()->diffInDays(Carbon::parse($deadline), false) : null;
    
    $completedTasksCount = $allTasks->where('status', 'completed')->count();
    $progressPercent = $allTasks->count() > 0 ? round(($completedTasksCount / $allTasks->count()) * 100, 2) : 0;

    return response()->json([
        'status' => true,
        'data' => [
            'project_id'   => $project->id,
            'project_name' => $project->name,
            'activity_notes' => $activityNotes,
            'dates' => [
                'start_date' => $startDate ? Carbon::parse($startDate)->toDateString() : null,
                'deadline'   => $deadline ? Carbon::parse($deadline)->toDateString() : null,
                'total_days' => (int)$totalDays,
                'days_left'  => (int)$daysLeft,
            ],
            
            'progress' => [
                'current_percent' => (int)$progressPercent,
                'current_status'  => $project->status, 
                'status_percentages' => $progressStatusPercentages, // % for every status
                'milestones_count' => $totalMilestones,
            ],

            'tasks' => [
                'status_counts' => $taskStatusCounts, // Numbers for every status
                'summary' => [
                    'total' => $allTasks->count(),
                    'completed' => $completedTasksCount,
                    'open' => $allTasks->where('status', '!=', 'completed')->count(),
                ],
                'items' => $allTasks->map(function ($task) {
                    return [
                        'id'      => $task->id,
                        'heading' => $task->heading,
                        'status'  => $task->status,
                        'due_date'=> $task->due_date,
                    ];
                }),
            ],

            'attachments' => $project->attachments->map(function ($file) {
                return [
                    'id'        => $file->id,
                    'file_name' => $file->file_name,
                    'file_url'  => asset('storage/' . $file->file_path), 
                    'uploaded_at' => $file->created_at->format('Y-m-d'),
                ];
            }),

            'total_rate' => $project->price + $project->getTotalAddonsAmount(),
            'meetings'   => $project->meetings,
        ],
    ]);
}

    public function getTaskSummaryForProject($projectId)
    {
        $user = auth()->user();

        $project = Project::where('id', $projectId)
            ->where('client_id', $user->id)
            ->with(['milestones.tasks'])
            ->first();

        if (!$project) {
            return response()->json(['status' => false, 'message' => 'Project not found or access denied.'], 404);
        }

        $tasks = $project->milestones->flatMap->tasks;

        $statusCounts = $tasks->groupBy('status')->map(function ($group) {
            return $group->count();
        });

        $allStatuses = ['not_started', 'in_progress', 'completed', 'awaiting_feedback', 'canceled','testing'];
        foreach ($allStatuses as $status) {
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = 0;
            }
        }

        $taskDetails = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'name' => $task->label,
                'description' => $task->description,
                'status' => $task->status,
                'start_date' => $task->start_date ? $task->start_date : null,
                'due_date' => $task->due_date ? $task->due_date: null,
                'priority' => $task->priority,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'total_tasks' => $tasks->count(),
                'status_counts' => $statusCounts,
                'task_details' => $taskDetails,
            ],
        ]);
    }
    
    public function getTaskSummaryForMilestone($milestoneId)
{
    $user = auth()->user();

    $milestone = Milestone::where('id', $milestoneId)
        ->whereHas('project', function ($query) use ($user) {
            $query->where('client_id', $user->id);
        })
        ->with('tasks')
        ->first();

    if (!$milestone) {
        return response()->json([
            'status' => false,
            'message' => 'Milestone not found or access denied.'
        ], 404);
    }

    $tasks = $milestone->tasks;

    $statusCounts = $tasks->groupBy('status')->map(fn ($group) => $group->count());

    $allStatuses = [
        'not_started',
        'in_progress',
        'completed',
        'awaiting_feedback',
        'canceled',
        'testing'
    ];

    foreach ($allStatuses as $status) {
        $statusCounts[$status] = $statusCounts[$status] ?? 0;
    }

    $taskDetails = $tasks->map(function ($task) {
        return [
            'id' => $task->id,
            'name' => $task->label,
            'description' => $task->description,
            'status' => $task->status,
            'start_date' => $task->start_date,
            'due_date' => $task->due_date,
            'priority' => $task->priority,
        ];
    });

    return response()->json([
        'status' => true,
        'data' => [
            'milestone' => [
                'id' => $milestone->id,
                'name' => $milestone->label,
                'phase' => $milestone->phase,
                'status' => $milestone->status,
            ],
            'total_tasks' => $tasks->count(),
            'status_counts' => $statusCounts,
            'task_details' => $taskDetails,
        ],
    ]);
}


    public function getAllAttachments($projectId)
    {
        $user = auth()->user();

        $project = Project::where('id', $projectId)
            ->where('client_id', $user->id)
            ->with('attachments')
            ->first();

        if (!$project) {
            return response()->json(['status' => false, 'message' => 'Project not found.'], 404);
        }

        $attachments = $project->attachments->map(function ($attachment) {
            $uploadedBy = null;
            if ($attachment->uploaded_by_id) {
                $uploadedBy = Client::find($attachment->uploaded_by_id);

                if (!$uploadedBy) {
                    $uploadedBy = Admin::find($attachment->uploaded_by_id);
                }
            }
            $filePath =  $attachment->file_path;
            $fileType = file_exists($filePath) ? mime_content_type($filePath) : 'unknown';

            return [
                'id' => $attachment->id,
                'file_path' => asset($attachment->file_path),
                'file_type' => $fileType,
                'uploaded_by_id' => $attachment->uploaded_by_id,
                'date_uploaded' => $attachment->created_at->toDateTimeString(),
                'last_activity' => $attachment->updated_at ? $attachment->updated_at->diffForHumans() : null,
                'uploaded_by' => $uploadedBy ? [
                    'id' => $uploadedBy->id,
                    'name' => $uploadedBy->name ?? 'Unknown',
                    'image' => $uploadedBy->image ?? null,
                    'type' => class_basename($uploadedBy),
                ] : null,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $attachments,
        ]);
    }

public function uploadAttachment(Request $request, $projectId)
{
    $request->validate([
        'attachments'   => 'required',
        'attachments.*' => 'file|max:10240',
    ]);

    $user = auth()->user();

    $project = Project::where('id', $projectId)
        ->where('client_id', $user->id)
        ->firstOrFail();

    // 🔹 Normalize files to array
    $files = $request->file('attachments');

    if (!is_array($files)) {
        $files = [$files];
    }

    foreach ($files as $file) {

        $path = ImageService::upload($file, 'attachments');

        if (!$path) {
            return response()->json([
                'status' => false,
                'message' => 'File upload failed'
            ], 500);
        }

        $project->attachments()->create([
            'file_path'        => $path,
            'uploaded_by_id'   => $user->id,
            'uploaded_by_type' => get_class($user),
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'Attachments uploaded successfully'
    ], 201);
}




    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user instanceof \App\Models\Admin) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $projects = Project::where('client_id', $user->id)
            ->with(['milestones', 'addons', 'attachments'])
            ->paginate(10);

        return response()->json([
            'status' => true,
            'message' => 'messages.list_success',
            'data' => [
                'data' => ProjectResource::collection($projects),
                'from' => $projects->firstItem(),
                'per_page' => $projects->perPage(),
                'to' => $projects->lastItem(),
                'total' => $projects->total(),
                'count' => $projects->count(),
            ],
        ]);
    }

    public function getDashboardSummary(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user instanceof \App\Models\Admin) {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $sliders = $this->sliderRepository->all();
        $slidersData = SliderResource::collection($sliders);

        $projects = Project::where('client_id', $user->id)
            ->with('milestones')
            ->get();

        $statusCounts = [
            'completed' => 0,
            'ongoing' => 0,
            'pending' => 0,
        ];

        foreach ($projects as $project) {
            $projectStatus = $project->status === 'not_approved' ? 'pending' : $project->status;
            $statusCounts[$projectStatus] = ($statusCounts[$projectStatus] ?? 0) + 1;

            if ($project->milestones->isNotEmpty()) {
                if ($project->milestones->every(fn($milestone) => $milestone->status === 'completed')) {
                    $statusCounts['completed']++;
                } else {
                    $statusCounts['ongoing']++;
                }
            } else {
                $statusCounts['ongoing']++;
            }
        }

        $invoiceCounts = [
            'paid' => 0,
            'unpaid' => 0,
            'overdue' => 0,
            'total' => 0,
        ];

        foreach ($projects as $project) {
            foreach ($project->invoices as $invoice) {
                $invoiceCounts['total']++;

                if ($invoice->status === 'paid') {
                    $invoiceCounts['paid']++;
                } elseif ($invoice->status === 'unpaid') {
                    if (Carbon::parse($invoice->due_date)->isPast()) {
                        $invoiceCounts['overdue']++;
                    } else {
                        $invoiceCounts['unpaid']++;
                    }
                }
            }
        }

        return response()->json([
            'status' => true,
            'data' => [
                'sliders' => $slidersData,
                'project_status_counts' => $statusCounts,
                'invoice_status_counts' => $invoiceCounts,
            ]
        ]);
    }
    public function deleteAttachment($attachmentId)
    {
        $attachment = Attachment::find($attachmentId);

        if (!$attachment) {
            return response()->json(['message' => 'Attachment not found.'], 404);
        }

        ImageService::delete($attachment->file_path);

        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully.'], 200);
    }



    public function listNames()
    {
        $employeeId = auth()->id(); 
        
        $projects = Project::select('id', 'name')
            ->whereHas('milestones.tasks.assignments', function ($q) use ($employeeId) {
                $q->where('employee_id', $employeeId);
            })
            ->get();

        return response()->json([
            'status' => true,
            'data' => $projects
        ]);
    }

public function getClientDashboardProjects(Request $request)
{
    $user = auth()->user();
    if (!$user || $user instanceof \App\Models\Admin) {
        return response()->json(['message' => 'Access denied.'], 403);
    }
    
    // Load relationships
    $projects = Project::with(['milestones.tasks.assignments.employee', 'attachments', 'addons', 'contract', 'proposals', 'product.sliders'])->orderByDesc('updated_at')
        ->where('client_id', $user->id)
        ->get();
    
    $projectsData = $projects->map(function ($project) {
        // --- 1. Manual Slider Logic (Bulletproof) ---
        $sliderImages = [];
        if ($project->product && $project->product->sliders) {
            foreach ($project->product->sliders as $slider) {
                // Get raw data to avoid any Resource/Accessor interference
                $imgs = $slider->getRawOriginal('image'); 
                $decoded = is_string($imgs) ? json_decode($imgs, true) : $imgs;
                
                if (is_array($decoded)) {
                    foreach ($decoded as $path) {
                        if (is_string($path) && !empty($path)) {
                            $sliderImages[] = url($path);
                        }
                    }
                } elseif (is_string($decoded) && !empty($decoded)) {
                    // If single string
                    $sliderImages[] = url($decoded);
                }
            }
        }
        
        // --- 2. Manual Proposals (Prevents ProductResource Crash) ---
        $proposals = $project->proposals->map(function($p) {
            return [
                'id' => $p->id,
                'status' => $p->status,
                'price' => $p->price,
            ];
        });
        
        // --- 3. Safe Attachments Handling ---
        $attachments = $project->attachments->map(function($attachment) {
            $path = null;
            if (isset($attachment->path)) {
                if (is_string($attachment->path)) {
                    $path = url($attachment->path);
                } elseif (is_array($attachment->path)) {
                    $firstPath = reset($attachment->path);
                    if (is_string($firstPath)) {
                        $path = url($firstPath);
                    }
                }
            }
            
            return [
                'id' => $attachment->id,
                'name' => $attachment->name ?? null,
                'path' => $path,
                // Add any other fields you need
            ];
        });
        
        // --- 4. Safe Team/Employee Handling ---
        $team = collect();
        if ($project->milestones->isNotEmpty()) {
            $employees = $project->milestones
                ->flatMap(function($milestone) {
                    return $milestone->tasks ?? collect();
                })
                ->flatMap(function($task) {
                    return $task->assignments ?? collect();
                })
                ->pluck('employee')
                ->filter()
                ->unique('id');
            
            $team = $employees->map(function($employee) {
                $avatar = null;
                
                if (isset($employee->image)) {
                    if (is_string($employee->image) && !empty($employee->image)) {
                        $avatar = url($employee->image);
                    } elseif (is_array($employee->image) && !empty($employee->image)) {
                        $firstImage = reset($employee->image);
                        if (is_string($firstImage) && !empty($firstImage)) {
                            $avatar = url($firstImage);
                        }
                    }
                }
                
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'avatar' => $avatar,
                ];
            });
        }
        
        // --- 5. Safe Contract Handling ---
        $contract = null;
        if ($project->contract) {
            $filePath = null;
            
            if (isset($project->contract->file_path)) {
                if (is_string($project->contract->file_path) && !empty($project->contract->file_path)) {
                    $filePath = url($project->contract->file_path);
                } elseif (is_array($project->contract->file_path) && !empty($project->contract->file_path)) {
                    $firstFile = reset($project->contract->file_path);
                    if (is_string($firstFile) && !empty($firstFile)) {
                        $filePath = url($firstFile);
                    }
                }
            }
            
            $contract = [
                'id' => $project->contract->id,
                'file_path' => $filePath,
            ];
        }
        
        // --- 6. Tasks Count ---
        $allTasks = $project->milestones->flatMap(function($milestone) {
            return $milestone->tasks ?? collect();
        });
        
        return [
            'id' => $project->id,
            'name' => $project->name,
            'status' => $project->status,
            'description' => $project->description,
            'slider_images' => $sliderImages,
            'proposals' => $proposals, 
            'attachments' => $attachments,
            'team' => $team,
            'tasks' => [
                'completed' => $allTasks->where('status', 'completed')->count(),
                'total' => $allTasks->count(),
            ],
            'contract' => $contract,
        ];
    });
    
    return response()->json([
        'status' => true,
        'data' => [
            'projects' => $projectsData,
            'status_cards' => [
                'all' => $projects->count(),
                'requested'=> $projects->where('status', 'requested')->count(),
                'completed' => $projects->where('status', 'completed')->count(),
                'ongoing' => $projects->where('status', 'ongoing')->count(),
                'pending' => $projects->where('status', 'requested')->count(),
            ],
        ]
    ]);
}


    
public function getProjectFullDetails(Request $request, $id)
{
    $user = auth()->user();
    $role = $user->role ?? null;

    $project = Project::with([
        'milestones.tasks.assignments.employee',

        // ✅ screens relations
        'milestones.tasks.screens.requestedApis.implementedApis',
        'milestones.tasks.devModeScreens',

        'addons',
        'invoices',
        'proposals',
    ])
    ->where('id', $id)
    ->where('client_id', $user->id)
    ->first();

    if (!$project) {
        return response()->json([
            'status' => false,
            'message' => 'Project not found or access denied'
        ], 404);
    }

    /* =======================
        BASIC CALCULATIONS
    ======================== */
                $team = $project->milestones
                ->flatMap->tasks
                ->flatMap->assignments
                ->pluck('employee')
                ->unique('id');

    $startDate = $project->milestones->min('start_date');
    $deadline  = $project->milestones->max('end_date');

    $completedTasks = $project->milestones->flatMap->tasks
        ->where('status', 'completed')
        ->count();

    $totalTasks = $project->milestones->flatMap->tasks->count();

    $taskPercentage = $totalTasks > 0
        ? round(($completedTasks / $totalTasks) * 100, 2)
        : 0;

    $team = $project->milestones
        ->flatMap->tasks
        ->flatMap->assignments
        ->pluck('employee')
        ->unique('id')
        ->values();

    /* =======================
        PROGRESS TIMELINE
    ======================== */

    $progressTimeline = collect(Milestone::PHASES)->mapWithKeys(function ($phase) use ($project) {
        $milestones = $project->milestones->where('phase', $phase);

        $completed = $milestones->where('status', 'completed')->count();
        $total     = $milestones->count();

        return [
            $phase => $total > 0 ? round(($completed / $total) * 100) : 0
        ];
    });

    /* =======================
        ACTIVITY NOTES
    ======================== */

    $date = $request->query('date'); // YYYY-MM-DD
    $filterDate = $date ? Carbon::parse($date)->toDateString() : null;

    $activityNotes = [];

    foreach ($project->milestones->flatMap->tasks->sortByDesc('updated_at') as $task) {
        if ($task->status !== 'completed') continue;
        if ($filterDate && $task->updated_at->toDateString() !== $filterDate) continue;

        $activityNotes[] = [
            'type' => 'task',
            'message' => "Task #{$task->id} completed by {$task->assignments->first()?->employee?->name}",
            'date' => $task->updated_at->toDateTimeString(),
        ];
    }

    foreach ($project->invoices->sortByDesc('updated_at') as $invoice) {
        if ($filterDate && $invoice->updated_at->toDateString() !== $filterDate) continue;

        $activityNotes[] = [
            'type' => 'invoice',
            'message' => "Invoice #{$invoice->id} issued",
            'date' => $invoice->updated_at->toDateTimeString(),
        ];
    }

    foreach ($project->proposals->sortByDesc('updated_at') as $proposal) {
        if ($filterDate && $proposal->updated_at->toDateString() !== $filterDate) continue;

        $activityNotes[] = [
            'type' => 'proposal',
            'message' => "Proposal #{$proposal->id} ({$proposal->status})",
            'date' => $proposal->updated_at->toDateTimeString(),
        ];
    }

    $activityNotes = collect($activityNotes)->sortByDesc('date')->values();

    /* =======================
        SCREEN VISIBILITY LOGIC
    ======================== */

    $getScreensByRole = function ($task) use ($role) {

        if ($role === 'front_end') {
            return $task->devModeScreens;
        }

        if ($role === 'back_end') {
            return $task->screens->filter(function ($screen) {
                return $screen->requestedApis->isNotEmpty();
            });
        }

        return $task->screens;
    };

    /* =======================
        RESPONSE
    ======================== */

    return response()->json([
        'status' => true,
        'data' => [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'description'=>$project->description,
                'deadline'=>$deadline,
                'start_date'=>$startDate,
                'budget'=>$project->price,
                'contract'=>$project->contract,
                                'team' => $team->map(fn($member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatar' => $member->image ?? null,
                ]),
                'milestones' => $project->milestones->map(function ($milestone) use ($getScreensByRole) {

                    return [
                        'id' => $milestone->id,
                        'name' => $milestone->label,
                        'phase' => $milestone->phase,
                        'status' => $milestone->status,
                        'start_date' => $milestone->start_date,
                        'end_date' => $milestone->end_date,

                        'tasks' => $milestone->tasks->map(function ($task) use ($getScreensByRole) {

                            $screens = $getScreensByRole($task);

                            return [
                                'id' => $task->id,
                                'title' => $task->label,
                                'status' => $task->status,
                                'description' => $task->description,
                                'priority' => $task->priority,
                                'updated_at' => $task->updated_at,

                                'assignees' => $task->assignments->map(fn ($a) => [
                                    'id' => $a->employee?->id,
                                    'name' => $a->employee?->name,
                                ])->values(),

                                // ✅ TASK SCREENS
                                'screens' => $screens->map(function ($screen) {
                                    return [
                                        'id' => $screen->id,
                                        'name' => $screen->name,
                                        'screen_code' => $screen->screen_code,
                                        'comment' => $screen->comment,
                                        'integrated' => $screen->integrated,
                                        'implemented' => $screen->implemented,
                                        'dev_mode' => $screen->dev_mode,

                                        'backend_approved' => $screen->requestedApis
                                            ->flatMap(fn ($req) => $req->implementedApis)
                                            ->where('status', 'tested')
                                            ->isNotEmpty(),
                                    ];
                                })->values(),
                            ];
                        }),
                    ];
                }),

                'notes' => $project->note,
                'type' => $project->created_by_type,
                'last_update' => $project->updated_at->diffForHumans(),
                'addons' => $project->addons->map(fn ($addon) => [
                    'id' => $addon->id,
                    'name' => $addon->name,
                    'price' => $addon->price
                ]),

                'progress_timeline' => $progressTimeline,
                'activity_notes' => $activityNotes,
                'open_tasks' => $totalTasks - $completedTasks,
                'days_left' => $deadline ? now()->diffInDays($deadline, false) : null,
            ]
        ]
    ]);
}


    // public function getProjectTasks($projectId)
    // {
    //     $user = auth()->user();


    //     $project = Project::where('id', $projectId)
    //         ->where('client_id', $user->id)
    //         ->first();

    //     if (!$project) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Project not found or access denied'
    //         ], 404);
    //     }


    //     $tasks = Task::with(['assignments.employee'])
    //         ->whereHas('milestone', fn($q) => $q->where('project_id', $projectId))
    //         ->get();


    //         $statusCounts = [
    //         'all' => $tasks->count(),
    //         'not_started' => $tasks->where('status', 'not_started')->count(),
    //         'in_progress' => $tasks->where('status', 'in_progress')->count(),
    //         'completed' => $tasks->where('status', 'completed')->count(),
    //         'awaiting_feedback' => $tasks->where('status', 'awaiting_feedback')->count(),
    //     ];


    //     $tasksData = $tasks->map(function ($task) {
    //         return [
    //             'id' => $task->id,
    //             'name' => $task->label,
    //             'start_date' => $task->start_date,
    //             'end_date' => $task->due_date,
    //             'status' => $task->status,

    //             'assigned_to' => $task->assignments->map(function ($assignment) {
    //                 return [
    //                     'id' => $assignment->employee->id,
    //                     'name' => $assignment->employee->name,
    //                     'image' => $assignment->employee->image ?? null,
    //                     'assignment_status' => $assignment->status,
    //                 ];
    //             }),
    //         ];
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'data' => [
    //             'cards' => $statusCounts,
    //             'tasks' => $tasksData,
    //         ]
    //     ]);
    // }



     public function getProjectAttachments($projectId)
    {
        $project = Project::findOrFail($projectId);

        $attachments = $project->attachments()
            ->with('uploadedBy')
            ->latest()
            ->get()
            ->map(function ($att) {
                return [
                    'id' => $att->id,
                    'file' => asset($att->file_path),
                    'uploaded_by' => $att->uploadedBy?->name ?? 'Unknown',
                    'uploaded_by_type' => class_basename($att->uploaded_by_type),
                    'created_at' => $att->created_at->format('Y-m-d H:i'),
                    'updated_at' => $att->updated_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $attachments
        ]);
    }

    public function getMilestoneTasks($milestoneId)
    {
        $user = auth()->user();

        $milestone = Milestone::where('id', $milestoneId)
            ->whereHas('project', function ($q) use ($user) {
                $q->where('client_id', $user->id);
            })
            ->first();

        if (!$milestone) {
            return response()->json([
                'status' => false,
                'message' => 'Milestone not found or access denied'
            ], 404);
        }

        $tasks = Task::with(['assignments.employee'])
            ->where('milestone_id', $milestoneId)
            ->get();

        $statusCounts = [
            'all' => $tasks->count(),
            'not_started' => $tasks->where('status', 'not_started')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'awaiting_feedback' => $tasks->where('status', 'awaiting_feedback')->count(),
        ];

    $tasksData = $tasks->map(function ($task) {
        $totalScreens = $task->screens->count();

        $completedScreens = $task->screens->filter(function ($screen) {
            // A screen is completed if all phases are done AND it has an image
            if (!$screen->dev_mode) return false; // Skip planning screens

            return $screen->implemented
                && $screen->integrated
                && $screen->frontend_approved
                && !empty($screen->image);
        })->count();

        $remainingScreens = $totalScreens - $completedScreens;

        $screenProgress = $totalScreens > 0
            ? round(($completedScreens / $totalScreens) * 100, 2)
            : 0;

return [
        'id' => $task->id,
        'name' => $task->label,
        'start_date' => $task->start_date,
        'end_date' => $task->due_date,
        'priority' => $task->priority,
        'status' => $task->status,
        'description' => $task->description,
        'assigned_to' => $task->assignments->map(function ($assignment) {
            return [
                'id' => $assignment->employee->id,
                'name' => $assignment->employee->name,
                'image' => $assignment->employee->image ?? null,
                'assignment_status' => $assignment->status,
            ];
        }),
        'screens' => [
            'completed' => $completedScreens,
            'remaining' => $remainingScreens,
            'progress_percentage' => $screenProgress,
            'list' => $task->screens->map(function ($screen) {
                return [
                    'id' => $screen->id,
                    'name' => $screen->name,
                    'dev_mode' => $screen->dev_mode,
                    'implemented' => $screen->implemented,
                    'integrated' => $screen->integrated,
                    'frontend_approved' => $screen->frontend_approved,
                    'screen_code' => $screen->screen_code,
                    'image' => $screen->image,
                    'estimated_hours' => $screen->estimated_hours,
                    'comment' => $screen->comment,
                    'created_at' => $screen->created_at?->toDateTimeString(),
                    'updated_at' => $screen->updated_at?->toDateTimeString(),
                ];
            }),
        ],
    ];
});

        return response()->json([
            'status' => true,
            'data' => [
                'milestone' => [
                    'id' => $milestone->id,
                    'description' => $milestone->description,
                    'name' => $milestone->label,
                    'start_date' => $milestone->start_date,
                    'end_date' => $milestone->end_date,
                    'cost' => $milestone->cost,
                    'status' => $milestone->status,
                ],
                'cards' => $statusCounts,
                'tasks' => $tasksData,
            ]
        ]);
    }

    public function getTaskFullDetails($taskId)
    {
        $task = Task::with([
            'milestone.project',
            'assignments.employee',
            'screens'
        ])->find($taskId);

        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found.',
            ], 404);
        }

       
        $totalScreens = $task->screens->count();
        $completedScreens = $task->screens->where('implemented', 1)->count();

        $progress = $totalScreens > 0
            ? round(($completedScreens / $totalScreens) * 100, 2)
            : 0;

         $completedScreensList = $task->screens
            ->where('implemented', 1)
            ->values()
            ->map(function ($screen) {
                return [
                    'id' => $screen->id,
                    'name' => $screen->name,
                    'screen_code' => $screen->screen_code,
                    'comment' => $screen->comment,
                    'image' => $screen->image ?? null,
                    'implemented' => $screen->implemented,
                    'integrated' => $screen->integrated,
                ];
            });

        $remainingScreensList = $task->screens
            ->where('implemented', 0)
            ->values()
            ->map(function ($screen) {
                return [
                    'id' => $screen->id,
                    'name' => $screen->name,
                    'screen_code' => $screen->screen_code,
                    'comment' => $screen->comment,
                    'image' => $screen->image ?? null,
                    'implemented' => $screen->implemented,
                    'integrated' => $screen->integrated,
                ];
            });

        // -----------------------------
        // 👤 الفريق Assigned Team
        // -----------------------------
        $team = $task->assignments->map(function ($assignment) {
            return [
                'id' => $assignment->employee->id,
                'name' => $assignment->employee->name,
                'image' => $assignment->employee->image ?? null,
                'status' => $assignment->status,
            ];
        });

        // -----------------------------
        // 🏁 Final Response
        // -----------------------------
        return response()->json([
            'status' => true,
            'message' => 'Task full details retrieved.',
            'data' => [
                'task' => [
                    'id' => $task->id,
                    'label' => $task->label,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'start_date' => $task->start_date,
                    'due_date' => $task->due_date,
                    'progress' => $progress,
                ],

                'project' => [
                    'id' => $task->milestone->project->id,
                    'name' => $task->milestone->project->name,
                ],

                'milestone' => [
                    'id' => $task->milestone->id,
                    'name' => $task->milestone->label,
                ],

                'team' => $team,

                'screens' => [
                    'completed' => $completedScreensList,
                    'remaining' => $remainingScreensList,
                ],
            ]
        ]);
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
}



