<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SliderResource;
use App\Models\Admin;
use App\Models\attachment;
use App\Models\Client;
use App\Models\Project;
use App\Repositories\ProjectRepositoryInterface;
use App\Repositories\SliderRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\ImageService;

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
            'product_id' => 'nullable|exists:products,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
            'status' => 'string|in:reject,completed,ongoing,requested',
            'addons' => 'array',
            'addons.*' => 'exists:addons,id',
            'attachments.*' => 'file|max:10240',
            'category_id' => 'nullable|exists:categories,id',
            // Remove 'client_id' from validation rules
        ]);
    
        $user = auth()->user();
    
        if (!$user instanceof \App\Models\Client) {
            return response()->json([
                'status' => false,
                'message' => 'Only clients can create projects.',
            ], 403);
        }
    
        if (isset($validatedData['price'])) {
            return response()->json([
                'status' => false,
                'message' => 'Only Admin can set the price.',
            ], 403);
        }
    
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
    
        if (!empty($validatedData['addons'])) {
            $product = $project->product;
            $productAddons = $product ? $product->addons->pluck('id')->toArray() : [];
    
            $newAddons = array_diff($validatedData['addons'], $productAddons);
    
            if (!empty($newAddons)) {
                $project->addons()->attach($newAddons);
            }
        }
    
        return response()->json(new ProjectResource($project->load(['attachments', 'addons'])), 201);
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
            'client_id' => 'nullable|exists:clients,id',
        ]);

        $user = auth()->user();

        if ($user instanceof \App\Models\Client && isset($validatedData['price'])) {
            return response()->json([
                'status' => false,
                'message' => 'Only Admin can update the price.',
            ], 403);
        }

        $project->update($validatedData);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = ImageService::upload($file, 'attachments');
                $project->attachments()->create([
                    'file_path' => $path,
                ]);
            }
        }

        if (isset($validatedData['addons'])) {
            $project->addons()->sync($validatedData['addons']);
        }

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
            ->where('client_id', $user->id)
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

}



