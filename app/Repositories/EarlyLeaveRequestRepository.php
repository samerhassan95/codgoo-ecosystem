<?php

namespace App\Repositories;

use App\Models\EarlyLeaveRequest;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\EarlyLeaveRequestRepositoryInterface;
use App\Http\Requests\EarlyLeaveRequestRequest;
use App\Http\Resources\EarlyLeaveRequestResource;

class EarlyLeaveRequestRepository extends CommonRepository implements EarlyLeaveRequestRepositoryInterface
{
    protected const REQUEST = EarlyLeaveRequestRequest::class;
    protected const RESOURCE = EarlyLeaveRequestResource::class;

    public function model(): string
    {
        return EarlyLeaveRequest::class;
    }
    
   
}