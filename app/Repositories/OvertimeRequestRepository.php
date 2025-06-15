<?php

namespace App\Repositories;

use App\Models\OvertimeRequest;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\OvertimeRequestRepositoryInterface;
use App\Http\Requests\OvertimeRequestRequest;
use App\Http\Resources\OvertimeRequestResource;

class OvertimeRequestRepository extends CommonRepository implements OvertimeRequestRepositoryInterface
{
    protected const REQUEST = OvertimeRequestRequest::class;
    protected const RESOURCE = OvertimeRequestResource::class;

    public function model(): string
    {
        return OvertimeRequest::class;
    }
    
   
}