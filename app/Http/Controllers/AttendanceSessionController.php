<?php

namespace App\Http\Controllers;

use App\Repositories\AttendanceSessionRepositoryInterface;

class AttendanceSessionController extends BaseController
{
    private $repository;

    public function __construct(AttendanceSessionRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
