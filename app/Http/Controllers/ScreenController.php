<?php

namespace App\Http\Controllers;

use App\Models\Screen;
use App\Repositories\ScreenRepositoryInterface;

class ScreenController extends BaseController
{
    private $repository;

    public function __construct(ScreenRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

     public function showWithRequestedApis($id)
    {
        $screen = Screen::with('requestedApis')->find($id);

        if (!$screen) {
            return response()->json([
                'status' => false,
                'message' => 'Screen not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Screen data retrieved successfully.',
            'data' => [
                'id' => $screen->id,
                'name' => $screen->name,
                'screen_code' => $screen->screen_code,
                'comment' => $screen->comment,
                'dev_mode' => $screen->dev_mode,
                'implemented' => $screen->implemented,
                'integrated' => $screen->integrated,
                'estimated_hours' => $screen->estimated_hours,
                'requested_apis' => $screen->requestedApis->map(function ($api) {
                    return [
                        'id' => $api->id,
                        'endpoint' => $api->endpoint,
                        'method' => $api->method,
                        'request_body' => $api->request_body,
                        'response_structure' => $api->response_structure,
                    ];
                }),
            ],
        ]);
    }
}
