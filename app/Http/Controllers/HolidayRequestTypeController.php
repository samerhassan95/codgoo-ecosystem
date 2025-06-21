<?php

namespace App\Http\Controllers;

use App\Http\Requests\HolidayRequestRequest;
use App\Http\Resources\HolidayRequestResource;
use App\Models\HolidayRequest;
use App\Repositories\HolidayRequestTypeRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayRequestTypeController extends BaseController
{
    private $repository;

    public function __construct(HolidayRequestTypeRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    public function getVisibleTypes(): JsonResponse
    {
        $types = $this->repository->getVisible();

        return response()->json([
            'status' => true,
            'message' => 'Visible holiday request types retrieved successfully.',
            'data' => $types,
        ]);
    }


}
