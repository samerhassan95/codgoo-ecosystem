<?php
namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Repositories\TaskRepositoryInterface;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\QueryBuilder;

class TaskController extends BaseController
{
    private $repository;

    public function __construct(TaskRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

      public function getTasksByMilestone($milestone_id)
      {
          $tasks = Task::where('milestone_id', $milestone_id)->get();

          return response()->json([
              'status' => true,
              'message' => 'Tasks fetched successfully for the milestone.',
              'data' => $tasks
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
              'data' => $tasks
          ], 200);
      }
}
