<?php

namespace App\Http\Controllers;

use App\Http\Requests\EarlyLeaveRequestRequest;
use App\Http\Resources\EarlyLeaveRequestResource;
use App\Models\EarlyLeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Repositories\EarlyLeaveRequestRepositoryInterface;

class EarlyLeaveRequestController extends BaseController
{
    private $repository;

    public function __construct(EarlyLeaveRequestRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
 
}
