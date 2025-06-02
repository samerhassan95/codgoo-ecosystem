<?php

namespace App\Repositories;

use App\Models\Attendance;
use Illuminate\Support\Collection;
use App\Repositories\Common\CommonRepository;
use App\Repositories\AttendanceRepositoryInterface;
use App\Http\Requests\AttendanceRequest;
use App\Http\Resources\AttendanceResource;


class AttendanceRepository extends CommonRepository implements AttendanceRepositoryInterface
{
    protected const REQUEST = AttendanceRequest::class;
    protected const RESOURCE = AttendanceResource::class;

    public function model(): string
    {
        return Attendance::class;
    }
   
}

