<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskAssignmentRequest;
use App\Http\Resources\TaskAssignmentResource;
use App\Repositories\TaskAssignmentRepositoryInterface;

class TaskAssignmentController extends BaseController
{
    private $repository;

    public function __construct(TaskAssignmentRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
