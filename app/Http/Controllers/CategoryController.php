<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Repositories\CategoryRepositoryInterface;

class CategoryController extends BaseController
{
    private $repository;

    public function __construct(CategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
