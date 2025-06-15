<?php

namespace App\Http\Controllers;

use App\Http\Requests\OvertimeRequestRequest;
use App\Http\Resources\OvertimeRequestResource;
use App\Models\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Repositories\OvertimeRequestRepositoryInterface;

class OvertimeRequestController extends BaseController
{
    private $repository;

    public function __construct(OvertimeRequestRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
    public function myRequests(): JsonResponse
    {
        $employee = auth()->user();

        $requests = OvertimeRequest::where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => true,
            'data' => OvertimeRequestResource::collection($requests),
        ]);
    }
}
