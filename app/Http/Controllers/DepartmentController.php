<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Repositories\DepartmentRepositoryInterface;

class DepartmentController extends BaseController
{
    private $repository;

    public function __construct(DepartmentRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
