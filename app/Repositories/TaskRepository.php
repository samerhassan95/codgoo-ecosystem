<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\TaskRepositoryInterface;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;

class TaskRepository extends CommonRepository implements TaskRepositoryInterface
{
    protected const REQUEST = TaskRequest::class;
    protected const RESOURCE = TaskResource::class;

    public function model(): string
    {
        return Task::class;
    }
    
    public function create(array $data)
    {
        return Task::create($data);
    }
}