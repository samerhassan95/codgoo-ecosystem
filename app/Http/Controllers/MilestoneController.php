<?php
namespace App\Http\Controllers;

use App\Http\Requests\MilestoneRequest;
use App\Http\Resources\MilestoneResource;
use App\Models\Client;
use App\Models\Milestone;
use App\Models\NotificationTemplate;
use App\Models\Project;
use App\Repositories\MilestoneRepositoryInterface;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MilestoneController extends BaseController
{
    private $repository;
    private $notificationRepository;
    private $firebaseService;

    public function __construct(
        MilestoneRepositoryInterface $repository,
        NotificationRepository $notificationRepository,
        FirebaseService $firebaseService
    ) {
        parent::__construct($repository);
        $this->repository = $repository;
        $this->notificationRepository = $notificationRepository;
        $this->firebaseService = $firebaseService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate((new MilestoneRequest)->rules());

        $project = Project::findOrFail($request->project_id);
        $projectBasePrice = $project->price;
        $totalAddons = $project->getTotalAddonsAmount();
        $totalMilestones = $project->milestones()->sum('cost');

        $allowedTotal = $projectBasePrice + $totalAddons;
        $totalAmountWithNewMilestone = $totalMilestones + $validated['cost'];

        if ($totalAmountWithNewMilestone > $allowedTotal) {
            return response()->json([
                'status' => false,
                'message' => 'The total amount with new milestone and addons exceeds the project price.'
            ], 400);
        }

        if (isset($validated['start_date']) && isset($validated['period'])) {
            $startDate = Carbon::parse($validated['start_date']);
            $dueDate = $startDate->addDays($validated['period']);
            $validated['end_date'] = $dueDate->toDateString();
        }

        $milestone = $this->repository->create($validated);

        $this->sendMilestoneCreatedNotification($milestone);

        return response()->json([
            'status' => true,
            'message' => 'Milestone created successfully',
            'data' => new MilestoneResource($milestone)
        ]);
    }

    public function update(Request $request, $id)
    {
        $milestone = $this->repository->find($id);

        if (!$milestone) {
            return response()->json(['status' => false, 'message' => 'Milestone not found.'], 404);
        }

        $oldStatus = $milestone->status;
        $milestone->update($request->all());

        if ($oldStatus !== $milestone->status) {
            $this->sendMilestoneStatusUpdatedNotification($milestone);
        }

        return response()->json([
            'status' => true,
            'message' => 'Milestone updated successfully.',
            'data' => new MilestoneResource($milestone)
        ]);
    }

    private function sendMilestoneCreatedNotification(Milestone $milestone)
    {
        $project = $milestone->project;
        if (!$project) return;

        $client = is_object($project->client) ? $project->client : Client::find($project->client_id);
        if (!$client) {
            Log::warning('No client found for project.', ['project_id' => $project->id]);
            return;
        }

        $template = NotificationTemplate::where('type', 'create_milestone')->first();
        if (!$template) {
            Log::warning('Notification template not found for milestone_created.');
            return;
        }

        $title = $template->title;
        $message = str_replace(
            ['{milestone}', '{project}'],
            [$milestone->label, $project->name],
            $template->message
        );

        $dataPayload = [
            'milestone_id' => $milestone->id,
            'notification_type' => 'create_milestone',
        ];

        if ($client->device_token) {
            $this->firebaseService->sendNotification($client->device_token, $title, $message, $dataPayload);
        } else {
            Log::warning('Client has no device token.', ['client_id' => $client->id]);
        }

        $this->notificationRepository->createNotification($client, $title, $message, $client->device_token, 'create_milestone');
    }

    private function sendMilestoneStatusUpdatedNotification(Milestone $milestone)
    {
        $client = $milestone->project->client ?? null;

        if ($client && $client->device_token) {
            $template = NotificationTemplate::where('type', 'milestone_status_update')->first();
            if ($template) {
                $title = $template->title;
                $message = str_replace(
                    ['{milestone}', '{status}', '{project}'],
                    [$milestone->label, $milestone->status, $milestone->project->name],
                    $template->message
                );

                $dataPayload = [
                    'milestone_id' => $milestone->id,
                    'notification_type' => 'milestone_status_update',
                ];

                $this->firebaseService->sendNotification($client->device_token, $title, $message, $dataPayload);
                $this->notificationRepository->createNotification($client, $title, $message, $client->device_token, 'milestone_status_update');
            }
        }
    }

    public function getMilestonesForProject($projectId)
    {
        $project = Project::find($projectId);

        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found.',
                'data' => null
            ], 404);
        }

        $milestones = Milestone::where('project_id', $projectId)->get();

        return response()->json([
            'status' => true,
            'message' => 'Milestones retrieved successfully.',
            'data' => MilestoneResource::collection($milestones)
        ], 200);
    }
}
