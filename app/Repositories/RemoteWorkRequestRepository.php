<?php

namespace App\Repositories;

use App\Models\RemoteWorkRequest;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\RemoteWorkRequestRepositoryInterface;
use App\Http\Requests\RemoteWorkRequestRequest;
use App\Http\Resources\RemoteWorkRequestResource;

class RemoteWorkRequestRepository extends CommonRepository implements RemoteWorkRequestRepositoryInterface
{
    protected const REQUEST = RemoteWorkRequestRequest::class;
    protected const RESOURCE = RemoteWorkRequestResource::class;

    public function model(): string
    {
        return RemoteWorkRequest::class;
    }
    
   
}