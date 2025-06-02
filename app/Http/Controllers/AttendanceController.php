<?php

namespace App\Http\Controllers;

use App\Repositories\AttendanceRepositoryInterface;

class AttendanceController extends BaseController
{
    public function __construct(AttendanceRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
