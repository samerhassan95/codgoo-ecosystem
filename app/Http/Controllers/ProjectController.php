<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SliderResource;
use App\Models\Admin;
use App\Models\attachment;
use App\Models\Client;
use App\Models\NotificationTemplate;
use App\Models\Project;
use App\Repositories\NotificationRepository;
use App\Repositories\ProjectRepositoryInterface;
use App\Repositories\SliderRepositoryInterface;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\ImageService;
use Illuminate\Support\Facades\Log;

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

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'addons' => 'array',
            'addons.*' => 'exists:addons,id',
            'attachments.*' => 'file|max:10240',
        ]);

        $user = auth()->user();

        if (!$user instanceof \App\Models\Client) {
            return response()->json([
                'status' => false,
                'message' => 'Only clients can create projects.',
            ], 403);
        }

        $addons = $validatedData['addons'] ?? [];
        unset($validatedData['addons']);
        unset($validatedData['attachments']);

        $validatedData['client_id'] = $user->id;

        $project = Project::create($validatedData);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = ImageService::upload($file, 'attachments');
                $project->attachments()->create([
                    'file_path' => $path,
                    'uploaded_by_id' => $user->id,
                ]);
            }
        }

        if (!empty($addons)) {
            $project->addons()->attach($addons);
        }

        $this->sendProjectCreatedNotification($project);

        return response()->json(new ProjectResource($project->load(['attachments', 'addons'])), 201);
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

        $project = Project::where('id', $projectId)
            ->with(['milestones.tasks', 'addons', 'meetings']) 
            ->first();

        if (!$project) {
            return response()->json(['status' => false, 'message' => 'Project not found or access denied.'], 404);
        }

        $milestones = $project->milestones;

        $startDate = $milestones->min('start_date');
        $deadline = $milestones->max('end_date');

        $totalDays = $startDate && $deadline ? Carbon::parse($startDate)->diffInDays(Carbon::parse($deadline)) : null;
        $daysLeft = $deadline ? now()->diffInDays(Carbon::parse($deadline), false) : null;

        $completedMilestones = $milestones->where('status', 'completed')->count();
        $totalMilestones = $milestones->count();
        $progress = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100, 2) : 0;

        $openTasks = $milestones->flatMap->tasks->where('status', '!=', 'completed')->count();
        $totalTasks = $milestones->flatMap->tasks->count();

        $addons = $project->addons->map(function ($addon) {
            return [
                'id' => $addon->id,
                'name' => $addon->name,
                'price' => $addon->price,
            ];
        });

        $totalRate = $project->price + $addons->sum('price');

        $meetings = $project->meetings->map(function ($meeting) {
            return [
                'id' => $meeting->id,
                'title' => $meeting->meeting_name,
                'end_time' => $meeting->end_time,
                'start_time' => $meeting->start_time,
                'meeting_link' => $meeting->jitsi_url,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'start_date' => $startDate ? Carbon::parse($startDate)->toDateString() : null,
                'deadline' => $deadline ? Carbon::parse($deadline)->toDateString() : null,
                'billing_type' => $project->billing_type,
                'total_rate' => $totalRate,
                'progress' => (int)$progress,
                'tasks' => [
                    'open_tasks' => (int)$openTasks,
                    'total_tasks' => (int)$totalTasks
                ],
                'total_days' => (int)$totalDays,
                'days_left' => (int)$daysLeft,
                'meetings' => $meetings,
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
        $validatedData = $request->validate([
            'attachments.*' => 'required|file|max:10240',
        ]);

        $user = auth()->user();

        $project = Project::where('id', $projectId)
            ->where('client_id', $user->id)
            ->first();

        if (!$project) {
            return response()->json(['status' => false, 'message' => 'Project not found.'], 404);
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = ImageService::upload($file, 'attachments');
                $project->attachments()->create([
                    'file_path' => $path,
                    'uploaded_by_id' => $user->id,
                ]);
            }
        }

        return response()->json(['status' => true, 'message' => 'Attachments uploaded successfully.'], 201);
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
        $user = auth()->user();

        $attachment = Attachment::where('id', $attachmentId)
            ->whereHas('project', function ($query) use ($user) {
                $query->where('client_id', $user->id);
            })
            ->first();

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


        $projects = Project::with(['milestones.tasks', 'attachments', 'addons'])
            ->where('client_id', $user->id)
            ->get();

        $statusCounts = [
            'all' => $projects->count(),
            'completed' => $projects->where('status', 'completed')->count(),
            'ongoing' => $projects->where('status', 'ongoing')->count(),
            'pending' => $projects->where('status', 'requested')->count(),
        ];

        $projectsData = $projects->map(function ($project) {
            $startDate = $project->milestones->min('start_date');
            $deadline = $project->milestones->max('end_date');


            $completedTasks = $project->milestones->flatMap->tasks->where('status', 'completed')->count();
            $totalTasks = $project->milestones->flatMap->tasks->count();


            $team = $project->milestones
                ->flatMap->tasks
                ->flatMap->assignments
                ->pluck('employee')
                ->unique('id');

            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'team' => $team->map(fn($member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatar' => $member->image ?? null,
                ]),
                'start_date' => $startDate ? $startDate : null,
                'deadline' => $deadline ? $deadline: null,
                'budget' => $project->price,
                'tasks' => [
                    'completed' => $completedTasks,
                    'total' => $totalTasks
                ],
                'status' => $project->status,
                'last_update' => $project->updated_at->diffForHumans(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'status_cards' => $statusCounts,
                'projects' => $projectsData,
            ]
        ]);
    }


    
    public function getProjectFullDetails($id)
    {
        $user = auth()->user();

        $project = Project::with([
            'milestones.tasks.assignments.employee',
            'attachments',
            'addons',
            'invoices',
            'proposals'
        ])
        ->where('id', $id)
        ->where('client_id', $user->id)
        ->first();

        if (!$project) {
            return response()->json(['status' => false, 'message' => 'Project not found or access denied'], 404);
        }


        $startDate = $project->milestones->min('start_date');
        $deadline = $project->milestones->max('end_date');
        $completedTasks = $project->milestones->flatMap->tasks->where('status', 'completed')->count();
        $totalTasks = $project->milestones->flatMap->tasks->count();
        $team = $project->milestones->flatMap->tasks->flatMap->assignments->pluck('employee')->unique('id');


        $progressTimeline = $project->milestones->groupBy('type')->map(function($milestones, $type) {
            $completed = $milestones->where('status', 'completed')->count();
            $total = $milestones->count();
            return $total > 0 ? round(($completed/$total)*100) : 0;
        });


        $activityNotes = [];

        foreach ($project->milestones->flatMap->tasks->sortByDesc('updated_at')->take(5) as $task) {
            if ($task->status === 'completed') {
                $activityNotes[] = [
                    'type' => 'task',
                    'message' => "Task #{$task->id} completed by {$task->assignments->first()?->employee?->name}",
                    'date' => $task->updated_at->toDateTimeString()
                ];
            }
        }

        foreach ($project->invoices->sortByDesc('updated_at')->take(5) as $invoice) {
            $activityNotes[] = [
                'type' => 'invoice',
                'message' => "Invoice #{$invoice->id} issued",
                'date' => $invoice->updated_at->toDateTimeString()
            ];
        }

        foreach ($project->proposals->sortByDesc('updated_at')->take(5) as $proposal) {
            $activityNotes[] = [
                'type' => 'proposal',
                'message' => "Client approved proposal #{$proposal->id}",
                'date' => $proposal->updated_at->toDateTimeString()
            ];
        }

        return response()->json([
            'status' => true,
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'team' => $team->map(fn($member) => [
                        'id' => $member->id,
                        'name' => $member->name,
                        'avatar' => $member->avatar ?? null
                    ]),
                    'start_date' => $startDate?->toDateString(),
                    'deadline' => $deadline?->toDateString(),
                    'budget' => $project->price,
                    'tasks' => [
                        'completed' => $completedTasks,
                        'total' => $totalTasks
                    ],
                    'status' => $project->status,
                    'last_update' => $project->updated_at->diffForHumans(),
                    'attachments' => $project->attachments->map(fn($att) => [
                        'id' => $att->id,
                        'file_path' => asset($att->file_path)
                    ]),
                    'addons' => $project->addons->map(fn($addon) => [
                        'id' => $addon->id,
                        'name' => $addon->name,
                        'price' => $addon->price
                    ]),
                    'progress_timeline' => $progressTimeline,
                    'activity_notes' => $activityNotes,
                    'open_tasks' => $totalTasks - $completedTasks,
                    'days_left' => $deadline ? now()->diffInDays($deadline, false) : null
                ]
            ]
        ]);
    }



}



