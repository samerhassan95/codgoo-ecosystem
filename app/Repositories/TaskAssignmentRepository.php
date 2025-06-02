<?php

namespace App\Repositories;

use App\Models\TaskAssignment;
use App\Repositories\Common\CommonRepository;
use App\Repositories\TaskAssignmentRepositoryInterface;
use App\Http\Requests\TaskAssignmentRequest;
use App\Http\Resources\TaskAssignmentResource;

class TaskAssignmentRepository extends CommonRepository implements TaskAssignmentRepositoryInterface
{
    protected const REQUEST = TaskAssignmentRequest::class;
    protected const RESOURCE = TaskAssignmentResource::class;

    public function model(): string
    {
        return TaskAssignment::class;
    }
}
