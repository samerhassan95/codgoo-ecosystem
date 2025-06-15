<?php

namespace App\Http\Controllers;

use App\Http\Requests\EarlyLeaveRequestRequest;
use App\Http\Resources\EarlyLeaveRequestResource;
use App\Models\EarlyLeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Repositories\EarlyLeaveRequestRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class EarlyLeaveRequestController extends BaseController
{
    private $repository;

    public function __construct(EarlyLeaveRequestRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    public function myRequests()
    {
        $employee = Auth::user();

        $requests = EarlyLeaveRequest::where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => true,
            'data' => EarlyLeaveRequestResource::collection($requests),
        ]);
    }

}
