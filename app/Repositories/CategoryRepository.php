<?php

namespace App\Repositories;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Repositories\CategoryRepositoryInterface;
use App\Repositories\Common\CommonRepository;

class CategoryRepository extends CommonRepository implements CategoryRepositoryInterface
{
    protected const REQUEST =CategoryRequest::class;
    protected const RESOURCE = CategoryResource::class;

    public function model(): string
    {
        return Category::class;
    }
}

