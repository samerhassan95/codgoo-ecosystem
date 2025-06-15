<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaperRequestRequest;
use App\Http\Resources\PaperRequestResource;
use App\Models\PaperRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Repositories\PaperRequestRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class PaperRequestController extends BaseController
{
    private $repository;

    public function __construct(PaperRequestRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    public function myRequests()
    {
        $employee = Auth::user();

        $requests = PaperRequest::where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => true,
            'data' => PaperRequestResource::collection($requests),
        ]);
    }
}
