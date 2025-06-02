<?php

namespace App\Repositories;

use App\Models\RequestedApi;
use App\Http\Requests\RequestedApiRequest;
use App\Http\Resources\RequestedApiResource;
use App\Repositories\Common\CommonRepository;

class RequestedApiRepository extends CommonRepository implements RequestedApiRepositoryInterface
{
    protected const REQUEST = RequestedApiRequest::class;
    protected const RESOURCE = RequestedApiResource::class;

    public function model(): string
    {
        return RequestedApi::class;
    }
}
