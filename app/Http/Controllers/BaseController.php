<?php

namespace App\Http\Controllers;

use App\Repositories\Common\CommonRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class BaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private $repository;

    public function __construct(CommonRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }


    public function index(Request $request)
    {
        return $this->repository->index($request);

    }



    public function store(Request $request)
    {
        return $this->repository->store($request);

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return $this->repository->show($id);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
{
    $validatedData = $request->all(); // Convert the request to an array

    $updatedModel = $this->repository->update($id, $validatedData);

    return response()->json($updatedModel, 200);
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        return $this->repository->delete($id);

    }

       protected function successResponse($data, $message = 'Success', $status = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function errorResponse($message = 'Error', $status = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $status);
    }
}
