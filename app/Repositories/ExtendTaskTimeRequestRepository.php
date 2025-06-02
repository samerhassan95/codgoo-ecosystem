<?php

namespace App\Repositories;

use App\Models\ExtendTaskTimeRequest;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\ExtendTaskTimeRequestRepositoryInterface;
use App\Http\Requests\ExtendTaskTimeRequestRequest;
use App\Http\Resources\ExtendTaskTimeRequestResource;

class ExtendTaskTimeRequestRepository extends CommonRepository implements ExtendTaskTimeRequestRepositoryInterface
{
    protected const REQUEST = ExtendTaskTimeRequestRequest::class;
    protected const RESOURCE = ExtendTaskTimeRequestResource::class;

    public function model(): string
    {
        return ExtendTaskTimeRequest::class;
    }
    
   
}