<?php

namespace App\Repositories;

use App\Models\ImplementedApi;
use App\Http\Requests\ImplementedApiRequest;
use App\Http\Resources\ImplementedApiResource;
use App\Repositories\Common\CommonRepository;

class ImplementedApiRepository extends CommonRepository implements ImplementedApiRepositoryInterface
{
    protected const REQUEST = ImplementedApiRequest::class;
    protected const RESOURCE = ImplementedApiResource::class;

    public function model(): string
    {
        return ImplementedApi::class;
    }
}
