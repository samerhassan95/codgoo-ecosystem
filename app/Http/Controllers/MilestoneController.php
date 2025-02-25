<?php
namespace App\Http\Controllers;

use App\Http\Requests\MilestoneRequest;
use App\Http\Resources\MilestoneResource;
use App\Models\Milestone;
use App\Models\NotificationTemplate;
use App\Models\Project;
use App\Repositories\MilestoneRepositoryInterface;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use App\Services\ImageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MilestoneController  extends BaseController
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

        if (!is_array($validated)) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed: invalid data structure.'
            ], 400);
        }

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
        $client = $milestone->project->client ?? null;

        if ($client && $client->device_token) {
            $template = NotificationTemplate::where('type', 'milestone_created')->first();

            if ($template) {
                $title = $template->title;
                $message = str_replace(
                    ['{milestone}', '{project}'],
                    [$milestone->name, $milestone->project->name],
                    $template->message
                );

                $this->firebaseService->sendNotification($client->device_token, $title, $message);
                $this->notificationRepository->createNotification($client, $title, $message, $client->device_token);
            }
        }
    }

    private function sendMilestoneStatusUpdatedNotification(Milestone $milestone)
    {
        $client = $milestone->project->client ?? null;

        if ($client && $client->device_token) {
            $template = NotificationTemplate::where('type', 'milestone_status_updated')->first();

            if ($template) {
                $title = $template->title;
                $message = str_replace(
                    ['{milestone}', '{status}', '{project}'],
                    [$milestone->name, $milestone->status, $milestone->project->name],
                    $template->message
                );

                $this->firebaseService->sendNotification($client->device_token, $title, $message);
                $this->notificationRepository->createNotification($client, $title, $message, $client->device_token);
            }
        }
    }


    public function getMilestonesForProject($projectId)
    {
        // Retrieve the project
        $project = Project::find($projectId);

        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found.',
                'data' => null
            ], 404);
        }

        // Retrieve all milestones related to the project
        $milestones = Milestone::where('project_id', $projectId)->get();

        // Check if there are any milestones
        if ($milestones->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No milestones found for this project.',
                'data' => []
            ], 200);
        }

        // Return milestones with a success response
        return response()->json([
            'status' => true,
            'message' => 'Milestones retrieved successfully.',
            'data' => $milestones
        ], 200);
    }


}
