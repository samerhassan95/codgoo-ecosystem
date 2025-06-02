<?php

namespace App\Repositories;

use App\Models\HolidayRequest;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\HolidayRequestRepositoryInterface;
use App\Http\Requests\HolidayRequestRequest;
use App\Http\Resources\HolidayRequestResource;

class HolidayRequestRepository extends CommonRepository implements HolidayRequestRepositoryInterface
{
    protected const REQUEST = HolidayRequestRequest::class;
    protected const RESOURCE = HolidayRequestResource::class;

    public function model(): string
    {
        return HolidayRequest::class;
    }
    
   
}