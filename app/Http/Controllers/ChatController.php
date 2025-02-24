<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ChatController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function getAllChats()
    {
        $chatSummaries = $this->firebaseService->getAllChats(); // الآن ترجع Array وليس JSON

        $page = request('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $paginated = new LengthAwarePaginator(
            array_slice($chatSummaries, $offset, $perPage),
            count($chatSummaries),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return response()->json([
            'from' => $paginated->firstItem() ?? 0,
            'per_page' => $paginated->perPage(),
            'to' => $paginated->lastItem() ?? 0,
            'total' => $paginated->total(),
            'count' => count($paginated->items()),
            'data' => $paginated->items(),
        ]);
    }

}
