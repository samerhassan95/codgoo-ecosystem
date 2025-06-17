<?php
namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\Employee;
use App\Models\NotificationTemplate;
use App\Repositories\TaskRepositoryInterface;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class TaskController extends BaseController
{
    private $repository;
    private $notificationRepository;
    private $firebaseService;

    public function __construct(TaskRepositoryInterface $repository, NotificationRepository $notificationRepository, FirebaseService $firebaseService)
    {
        parent::__construct($repository);
        $this->repository = $repository;
        $this->notificationRepository = $notificationRepository;
        $this->firebaseService = $firebaseService;
    }

    public function getTasksByMilestone($milestone_id)
    {
        $tasks = Task::where('milestone_id', $milestone_id)->get();

        return response()->json([
            'status' => true,
            'message' => 'Tasks fetched successfully for the milestone.',
            'data' => TaskResource::collection($tasks)
        ], 200);
    }

    public function getTasksByProject($project_id, Request $request)
    {
        $tasks = QueryBuilder::for(Task::class)
            ->whereHas('milestone', function ($query) use ($project_id) {
                $query->where('project_id', $project_id);
            })
            ->allowedFilters([
                'label',
                'status',
                'priority',
            ])
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Tasks fetched successfully for the project.',
            'data' => TaskResource::collection($tasks)
        ], 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate((new TaskRequest())->rules());

        $task = $this->repository->create($validatedData);

        $milestone = $task->milestone;
        $client = $milestone->project->client ?? null; 

        if ($client && $client->device_token) {
            $template = NotificationTemplate::where('type', 'create_task')->first();
            if ($template) {
                $title = $template->title;
                $message = str_replace(
                    ['{label}', '{milestone}'],
                    [$task->label, $milestone->label],
                    $template->message
                );

                $dataPayload = [
                    'task_id' => $task->id,
                    'notification_type' => 'create_task',
                ];

                $this->firebaseService->sendNotification($client->device_token, $title, $message, $dataPayload);
                $this->notificationRepository->createNotification($client, $title, $message, $client->device_token, 'create_task');
            }
        }

        if ($task->assigned_to) {
            $employee = Employee::find($task->assigned_to);
            if ($employee && $employee->device_token) {
                $template = NotificationTemplate::where('type', 'assigne_task')->first();
                if ($template) {
                    $title = $template->title;
                    $message = str_replace(
                        ['{label}', '{milestone}'],
                        [$task->label, $milestone->name],
                        $template->message
                    );

                    $dataPayload = [
                        'task_id' => $task->id,
                        'notification_type' => 'assigne_task',
                    ];

                    $this->firebaseService->sendNotification($employee->device_token, $title, $message, $dataPayload);
                    $this->notificationRepository->createNotification($employee, $title, $message, $employee->device_token, 'assigne_task');
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Task created successfully, and notifications sent.',
            'data' => new TaskResource($task),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $task = $this->repository->find($id);

        if (!$task) {
            return response()->json(['status' => false, 'message' => 'Task not found.'], 404);
        }

        $oldStatus = $task->status;
        $task->update($request->all());

        if ($oldStatus !== $task->status) {
            $this->sendStatusUpdateNotification($task);
        }

        return response()->json([
            'status' => true,
            'message' => 'Task updated successfully.',
            'data' => new TaskResource($task),
        ], 200);
    }

    private function sendStatusUpdateNotification(Task $task)
    {
        $milestone = $task->milestone;
        $client = $milestone->project->client ?? null;
        $employee = $task->assignedEmployee;

        $template = NotificationTemplate::where('type', 'update_task_status')->first();
        if ($template) {
            $title = $template->title;
            $message = str_replace(
                ['{label}', '{status}', '{milestone}'],
                [$task->label, $task->status, $milestone->label],
                $template->message
            );

            $dataPayload = [
                'task_id' => $task->id,
                'notification_type' => 'update_task_status',
            ];

            if ($client && $client->device_token) {
                $this->firebaseService->sendNotification($client->device_token, $title, $message, $dataPayload);
                $this->notificationRepository->createNotification($client, $title, $message, $client->device_token, 'update_task_status');
            }

            if ($employee && $employee->device_token) {
                $this->firebaseService->sendNotification($employee->device_token, $title, $message, $dataPayload);
                $this->notificationRepository->createNotification($employee, $title, $message, $employee->device_token, 'update_task_status');
            }
        }
    }


    public function employeeTasks(Request $request)
    {
        $employee = auth('employee')->user();

        $query = Task::whereHas('assignments', function ($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })
        ->with([
            'assignments' => function ($q) use ($employee) {
                $q->where('employee_id', $employee->id);
            },
            'milestone.project',
        ]);

        if ($request->filled('date')) {
            $query->whereDate('start_date', '<=', $request->date)
                ->whereDate('due_date', '>=', $request->date);
        }

        if ($request->filled('project_id')) {
            $query->whereHas('milestone.project', function ($q) use ($request) {
                $q->where('id', $request->project_id);
            });
        }

        if ($request->filled('status')) {
            $query->whereHas('assignments', function ($q) use ($employee, $request) {
                $q->where('employee_id', $employee->id)
                ->where('status', $request->status);
            });
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('start_date', '>=', $request->from_date)
                ->whereDate('due_date', '<=', $request->to_date);
            });
        }

        $tasks = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Tasks retrieved successfully.',
            'data' => $tasks->map(function ($task) {
                $assignment = $task->assignments->first();

                return [
                    'id' => $task->id,
                    'label' => $task->label,
                    'description' => $task->description,
                    'start_date' => $task->start_date,
                    'due_date' => $task->due_date,
                    'priority' => $task->priority,
                    'status' => $assignment?->status ?? $task->status,
                    'estimated_hours' => $assignment?->estimated_hours,
                    'header' => $assignment?->header,
                    'project' => $task->milestone->project->name ?? null,
                ];
            }),
        ]);
    }


    public function showTaskWithScreensforUI($id)
    {
        $task = Task::with(['screens', 'milestone.project'])->find($id);

        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Task details retrieved successfully.',
            'data' => [
                'id' => $task->id,
                'label' => $task->label,
                'description' => $task->description,
                'start_date' => $task->start_date,
                'due_date' => $task->due_date,
                'priority' => $task->priority,
                'status' => $task->status,
                'project' => $task->milestone->project->name ?? null,
                'screens' => $task->screens->map(function ($screen) {
                    return [
                        'id' => $screen->id,
                        'name' => $screen->name,
                        'screen_code' => $screen->screen_code,
                        'comment' => $screen->comment,
                        'integrated' => $screen->integrated,
                        'implemented' => $screen->implemented,
                        'dev_mode' => $screen->dev_mode,
                    ];
                }),
            ],
        ]);
    }

     public function showTaskWithScreensfront($id)
    {
        $task = Task::with(['devModeScreens', 'milestone.project'])->find($id);

        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Task details retrieved successfully.',
            'data' => [
                'id' => $task->id,
                'label' => $task->label,
                'description' => $task->description,
                'start_date' => $task->start_date,
                'due_date' => $task->due_date,
                'priority' => $task->priority,
                'status' => $task->status,
                'project' => $task->milestone->project->name ?? null,
                'dev_mode_screens ' => $task->devModeScreens->map(function ($screen) {
                    return [
                        'id' => $screen->id,
                        'name' => $screen->name,
                        'screen_code' => $screen->screen_code,
                        'comment' => $screen->comment,
                        'integrated' => $screen->integrated,
                        'implemented' => $screen->implemented,
                        'dev_mode' => $screen->dev_mode,
                    ];
                }),
            ],
        ]);
    }

    public function showTaskWithScreensback($id)
    {
        $task = Task::with(['screens.requestedApis', 'milestone.project'])->find($id);

        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'Task not found.',
                'data' => null,
            ], 404);
        }

        $filteredScreens = $task->screens->filter(function ($screen) {
            return $screen->requestedApis->isNotEmpty();
        });

        return response()->json([
            'status' => true,
            'message' => 'Task details retrieved successfully.',
            'data' => [
                'id' => $task->id,
                'label' => $task->label,
                'description' => $task->description,
                'start_date' => $task->start_date,
                'due_date' => $task->due_date,
                'priority' => $task->priority,
                'status' => $task->status,
                'project' => $task->milestone->project->name ?? null,
                'screens' => $filteredScreens->map(function ($screen) {
                    return [
                        'id' => $screen->id,
                        'name' => $screen->name,
                        'screen_code' => $screen->screen_code,
                        'comment' => $screen->comment,
                        'integrated' => $screen->integrated,
                        'implemented' => $screen->implemented,
                        'dev_mode' => $screen->dev_mode,
                    ];
                })->values(),
            ],
        ]);
    }


}
