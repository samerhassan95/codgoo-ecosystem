<?php

namespace App\Repositories;

use App\Models\HolidayRequestType;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\HolidayRequestTypeRepositoryInterface;
use App\Http\Requests\HolidayRequestTypeRequest;
use App\Http\Resources\HolidayRequestTypeResource;

class HolidayRequestTypeRepository extends CommonRepository implements HolidayRequestTypeRepositoryInterface
{
    protected const REQUEST = HolidayRequestTypeRequest::class;
    protected const RESOURCE = HolidayRequestTypeResource::class;

    public function model(): string
    {
        return HolidayRequestType::class;
    }
    public function getVisible()
    {
        return HolidayRequestType::where('visible', true)->get();
    }


}