<?php

namespace App\Http\Controllers;

use App\Models\Screen;
use App\Repositories\ScreenRepositoryInterface;
use Illuminate\Http\Request;

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


    public function getScreensWithReviewsByRole(Request $request)
    {
        $user = auth()->user();

        $roleToReviewType = [
            'ui_ux'     => 'ui',
            'front_end' => 'frontend',
            'back_end'  => 'backend',
            'mobile'    => 'mobile',
            'tester'    => null,
        ];

        $reviewType = $roleToReviewType[$user->role] ?? null;

        $screens = Screen::whereHas('reviews', function ($query) use ($reviewType, $user) {
            $query->where('is_resolved', false); // ❗ فقط الكومنتات الغير محلولة
            if ($user->role !== 'tester' && $reviewType) {
                $query->where('review_type', $reviewType);
            }
        })
        ->with([
            'task:id,label',
            'reviews' => function ($query) use ($reviewType, $user) {
                $query->where('is_resolved', false); // ❗ الكومنتات الغير محلولة فقط
                if ($user->role !== 'tester' && $reviewType) {
                    $query->where('review_type', $reviewType);
                }
                $query->with('creator:id,name');
            }
        ])
        ->get()
        ->map(function ($screen) {
            return [
                'screen_id'   => $screen->id,
                'screen_name' => $screen->name,
                'screen_code' => $screen->screen_code,
                'dev_mode'    => $screen->dev_mode,
                'task_name'   => $screen->task->label ?? null,
                'comments'    => $screen->reviews->map(function ($review) {
                    return [
                        'creator_name' => $review->creator->name ?? 'Unknown',
                        'id'           => $review->id,
                        'comment'      => $review->comment,
                        'created_at'   => $review->created_at->toDateTimeString(),
                    ];
                }),
            ];
        });

        return response()->json([
            'status'  => true,
            'screens' => $screens
        ]);
    }



}
