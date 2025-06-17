<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImplementedApiRequest;
use App\Http\Resources\ImplementedApiResource;
use App\Repositories\ImplementedApiRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImplementedApiController extends BaseController
{
    private $repository;

    public function __construct(ImplementedApiRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'requested_api_ids' => 'required|array',
            'requested_api_ids.*' => 'exists:requested_apis,id',
            // 'status' => 'in:pending,complete,tested',
            'postman_collection_url' => 'nullable|string',
        ]);

        $status ='complete';
        $url = $validated['postman_collection_url'] ?? null;

        foreach ($validated['requested_api_ids'] as $apiId) {
            \App\Models\ImplementedApi::firstOrCreate(
                ['requested_api_id' => $apiId],
                ['postman_collection_url' => $url]
            );
        }

        return response()->json([
            'message' => 'APIs marked as implemented successfully.',
        ], 201);
    }


}
