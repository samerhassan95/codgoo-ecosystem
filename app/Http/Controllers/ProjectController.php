<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Project;
use App\Repositories\ProjectRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\ImageService;

class ProjectController extends BaseController
{
    private $repository;

    public function __construct(ProjectRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
            'status' => 'string|in:approved,not_approved,canceled',
            'addons' => 'array',
            'addons.*' => 'exists:addons,id',
            'attachments.*' => 'file|max:10240',
        ]);

        $user = auth()->user();
        $type = $user instanceof \App\Models\Admin ? 'Admin' : 'Client';

        $validatedData['created_by_id'] = $user->id;
        $validatedData['created_by_type'] = $type;

        if ($type === 'Client' && isset($validatedData['price'])) {
            return response()->json([
                'status' => false,
                'message' => 'Only Admin can set the price.',
            ], 403);
        }

        $project = Project::create(collect($validatedData)->except(['attachments', 'addons'])->toArray());

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = ImageService::upload($file, 'attachments');
                $project->attachments()->create([
                    'file_path' => $path,
                ]);
            }
        }

        if (!empty($validatedData['addons'])) {
            $project->addons()->attach($validatedData['addons']);
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
            'status' => 'nullable|string|in:approved,not_approved,canceled',
            'addons' => 'array',
            'addons.*' => 'exists:addons,id',
            'attachments.*' => 'file|max:10240',
        ]);

        $user = auth()->user();
        $type = $user instanceof \App\Models\Admin ? 'Admin' : 'Client';

        if ($type === 'Client' && isset($validatedData['price'])) {
            return response()->json([
                'status' => false,
                'message' => 'Only Admin can update the price.',
            ], 403);
        }

        $project->update(collect($validatedData)->except(['attachments', 'addons'])->toArray());

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

        $projects = Project::where('created_by_id', $user->id)
            ->where('created_by_type', 'Client')
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
            3 => 'pending',
        ];
    
        if (!array_key_exists($status, $statusMapping)) {
            return response()->json(['message' => 'Invalid status. Valid statuses are: 1 (completed), 2 (ongoing), 3 (pending).'], 400);
        }
    
        $statusString = $statusMapping[$status];
    
        $user = auth()->user();
    
        if (!$user || $user instanceof \App\Models\Admin) {
            return response()->json(['message' => 'Access denied.'], 403);
        }
    
        $projects = Project::where('created_by_id', $user->id)
            ->where('created_by_type', 'Client')
            ->with('milestones') 
            ->get();
    
        if ($statusString === 'all') {
            return response()->json([
                'status' => true,
                'data' => $projects,
            ]);
        }
    
        $filteredProjects = [];
    
        foreach ($projects as $project) {
            $projectStatus = $project->status === 'not_approved' ? 'pending' : $project->status;
    
            if ($statusString === 'completed') {
                if ($project->milestones->isNotEmpty() && $project->milestones->every(fn($milestone) => $milestone->status === 'completed')) {
                    $filteredProjects[] = $project;
                }
            }
    
            if ($statusString === 'ongoing') {
                if ($project->milestones->isNotEmpty() && ($project->milestones->contains('in_progress') || $project->milestones->contains('not_started'))) {
                    $filteredProjects[] = $project;
                }
            }
    
            if ($statusString === 'pending') {
                if ($project->milestones->isEmpty() || $projectStatus === 'pending') {
                    $filteredProjects[] = $project;
                }
            }
        }
    
        return response()->json([
            'status' => true,
            'data' => $filteredProjects,
        ]);
    }
    


    public function getProjectDetails($projectId)
    {
        $user = auth()->user();

        $project = Project::where('id', $projectId)->first();

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
                'progress' => $progress,
                'tasks' => [
                    'open_tasks' => $openTasks,
                    'total_tasks' => $totalTasks
                ],
                'total_days' => $totalDays,
                'days_left' => $daysLeft,
            ],
        ]);
    }



    public function getTaskSummaryForProject($projectId)
    {
        $user = auth()->user();

        $project = Project::where('id', $projectId)
            ->where('created_by_id', $user->id)
            ->where('created_by_type', 'Client')
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
        $project = Project::with('attachments')->find($projectId);

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

        $project = Project::find($projectId);

        if (!$project) {
            return response()->json(['status' => false, 'message' => 'Project not found.'], 404);
        }
        $user = auth()->user();
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

        $projects = Project::where('created_by_id', $user->id)
            ->where('created_by_type', 'Client')
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


}



