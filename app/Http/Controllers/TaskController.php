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

                $this->firebaseService->sendNotification($client->device_token, $title, $message);
                $this->notificationRepository->createNotification($client, $title, $message, $client->device_token);
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

                    $this->firebaseService->sendNotification($employee->device_token, $title, $message);
                    $this->notificationRepository->createNotification($employee, $title, $message, $employee->device_token);
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Task created successfully, and notifications sent.',
            'data' => new TaskResource($task),
        ], 201);
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

            if ($client && $client->device_token) {
                $this->firebaseService->sendNotification($client->device_token, $title, $message);
                $this->notificationRepository->createNotification($client, $title, $message, $client->device_token);
            }

            if ($employee && $employee->device_token) {
                $this->firebaseService->sendNotification($employee->device_token, $title, $message);
                $this->notificationRepository->createNotification($employee, $title, $message, $employee->device_token);
            }
        }
    }

}
