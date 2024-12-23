<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopicGalleryRequest;
use App\Http\Resources\TopicGalleryResource;
use App\Models\TopicGallery;
use App\Models\Project;
use App\Repositories\TopicGalleryRepositoryInterface;
use Illuminate\Http\Request;


class TopicGalleryController extends BaseController
{
    private $repository;

    public function __construct(TopicGalleryRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }


    // public function getTopicGallerysForProject($projectId)
    // {
    //     // Retrieve the project
    //     $project = Project::find($projectId);

    //     if (!$project) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Project not found.',
    //             'data' => null
    //         ], 404);
    //     }

    //     // Retrieve all TopicGallerys related to the project
    //     $TopicGallerys = TopicGallery::where('project_id', $projectId)->get();

    //     // Check if there are any TopicGallerys
    //     if ($TopicGallerys->isEmpty()) {
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'No TopicGallerys found for this project.',
    //             'data' => []
    //         ], 200);
    //     }

    //     // Return TopicGallerys with a success response
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'TopicGallerys retrieved successfully.',
    //         'data' => $TopicGallerys
    //     ], 200);
    // }
}
