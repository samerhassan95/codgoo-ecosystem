<?php

namespace App\Repositories;

use App\Models\AttendanceSession;
use App\Repositories\Common\CommonRepository;
use App\Http\Requests\AttendanceSessionRequest;
use App\Http\Resources\AttendanceSessionResource;

class AttendanceSessionRepository extends CommonRepository implements AttendanceSessionRepositoryInterface
{
    protected const REQUEST = AttendanceSessionRequest::class;
    protected const RESOURCE = AttendanceSessionResource::class;

    public function model(): string
    {
        return AttendanceSession::class;
    }
}
