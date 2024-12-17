<?php
namespace App\Http\Controllers;

use App\Http\Requests\MilestoneRequest;
use App\Http\Resources\MilestoneResource;
use App\Models\Milestone;
use App\Models\Project;
use App\Repositories\MilestoneRepositoryInterface;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MilestoneController  extends BaseController
{
    private $repository;

    public function __construct(MilestoneRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
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


        $milestone = $this->repository->create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Milestone created successfully',
            'data' => $milestone
        ]);
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
