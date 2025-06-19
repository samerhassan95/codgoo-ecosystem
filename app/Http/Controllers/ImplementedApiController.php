<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImplementedApiRequest;
use App\Http\Resources\ImplementedApiResource;
use App\Repositories\ImplementedApiRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\ImplementedApi;

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


    public function markAsTested(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:implemented_apis,id',
        ]);

        ImplementedApi::whereIn('id', $request->ids)->update(['status' => 'tested']);

        return response()->json([
            'status' => true,
            'message' => 'Selected APIs marked as tested successfully.',
        ]);
    }


}
