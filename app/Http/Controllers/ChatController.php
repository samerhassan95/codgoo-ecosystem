<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function getAllChats(): JsonResponse
    {
        $chatSummaries = $this->firebaseService->getAllChats(); 

        $page = request('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($chatSummaries, $offset, $perPage);

        $pagination = new LengthAwarePaginator(
            $paginatedData,
            count($chatSummaries),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return response()->json([
            'from' => $pagination->firstItem() ?? 0,
            'per_page' => $pagination->perPage(),
            'to' => $pagination->lastItem() ?? 0,
            'total' => $pagination->total(),
            'count' => count($paginatedData),
            'data' => $pagination->items(),
        ]);
    }
}
