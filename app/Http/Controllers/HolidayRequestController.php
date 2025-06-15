<?php

namespace App\Http\Controllers;

use App\Http\Requests\HolidayRequestRequest;
use App\Http\Resources\HolidayRequestResource;
use App\Models\HolidayRequest;
use App\Repositories\HolidayRequestRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayRequestController extends BaseController
{
    private $repository;

    public function __construct(HolidayRequestRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    public function myRequests(): JsonResponse
    {
        $employeeId = auth()->id();

        $requests = HolidayRequest::where('employee_id', $employeeId)
            ->with('employee', 'holidayRequestType')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => HolidayRequestResource::collection($requests),
        ]);
    }

    // public function index(): JsonResponse
    // {
    //     $requests = HolidayRequest::with('employee')->get();
    //     return response()->json(HolidayRequestResource::collection($requests));
    // }


    // public function store(HolidayRequestRequest $request): JsonResponse
    // {
    //     $holidayRequest = HolidayRequest::create([
    //         'employee_id' => auth()->id(),
    //         'description' => $request->description,
    //         'date_from' => $request->date_from,
    //         'date_to' => $request->date_to,
    //         'status' => 'pending',
    //     ]);

    //     return response()->json(new HolidayRequestResource($holidayRequest), 201);
    // }


    // public function show(HolidayRequest $holidayRequest): JsonResponse
    // {
    //     return response()->json(new HolidayRequestResource($holidayRequest));
    // }

    // public function update(HolidayRequestRequest $request, HolidayRequest $holidayRequest): JsonResponse
    // {
    //     $holidayRequest->update($request->validated());

    //     return response()->json(new HolidayRequestResource($holidayRequest));
    // }


    // public function destroy(HolidayRequest $holidayRequest): JsonResponse
    // {
    //     $holidayRequest->delete();

    //     return response()->json(['message' => 'Holiday request deleted successfully.']);
    // }
}
