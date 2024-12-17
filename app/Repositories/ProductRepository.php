<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Attachment;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\ProductRepositoryInterface;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;

class ProductRepository extends CommonRepository implements ProductRepositoryInterface
{
    protected const REQUEST = ProductRequest::class;
    protected const RESOURCE = ProductResource::class;

    public function model(): string
    {
        return Product::class;
    }
}