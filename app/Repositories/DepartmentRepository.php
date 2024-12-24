<?php

namespace App\Repositories;

use App\Http\Requests\DepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Repositories\DepartmentRepositoryInterface;
use App\Repositories\Common\CommonRepository;

class DepartmentRepository extends CommonRepository implements DepartmentRepositoryInterface
{
    protected const REQUEST =DepartmentRequest::class;
    protected const RESOURCE = DepartmentResource::class;

    public function model(): string
    {
        return Department::class;
    }
}

