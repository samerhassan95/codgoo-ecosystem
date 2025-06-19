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
                'frontend_approved' => $screen->frontend_approved,
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
            $query->where('is_resolved', false); // 
            if ($user->role !== 'tester' && $reviewType) {
                $query->where('review_type', $reviewType);
            }
        })
        ->with([
            'task:id,label',
            'reviews' => function ($query) use ($reviewType, $user) {
                $query->where('is_resolved', false); 
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
   
    public function getScreenWithReviewsByType(Request $request, $id)
    {
        $reviewType = $request->get('review_type');
        $validTypes = ['backend', 'frontend', 'ui'];

        if ($reviewType && !in_array($reviewType, $validTypes)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid review type.',
            ], 400);
        }

        $screen = Screen::with([
            'task:id,label',
            'reviews' => function ($query) use ($reviewType) {
                if ($reviewType) {
                    $query->where('review_type', $reviewType);
                }
                $query->with('creator:id,name');
            },
            'requestedApis'
        ])->find($id);

        if (!$screen) {
            return response()->json([
                'status' => false,
                'message' => 'Screen not found.',
            ], 404);
        }

        if ($reviewType === 'backend' && $screen->requestedApis->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'This screen is not backend-related (no requested APIs).',
            ], 200);
        }

        $screenData = [
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

        if ($reviewType === 'backend') {
            $screenData['apis'] = $screen->requestedApis->map(function ($api) {
                return [
                    'id' => $api->id,
                    'endpoint' => $api->endpoint,
                    'method' => $api->method,
                    'request_body' => $api->request_body,
                    'response_structure' => $api->response_structure,
                ];
            });
        }

        return response()->json([
            'status' => true,
            'screen' => $screenData,
        ]);
    }

    public function getScreenDevelopmentOverview($id)
    {
        $screen = Screen::with([
            'task.assignments.employee',
            'requestedApis.implementedApis'
        ])->find($id);

        if (!$screen) {
            return response()->json([
                'status' => false,
                'message' => 'Screen not found.'
            ], 404);
        }

        $task = $screen->task;

        $frontendDev = $task->assignments->firstWhere('employee.role', 'front_end')?->employee;
        $backendDev = $task->assignments->firstWhere('employee.role', 'back_end')?->employee;
        $uiDev = $task->assignments->firstWhere('employee.role', 'ui_ux')?->employee;

        $uiDeveloperStatus = 'completed';
        $uiTesterStatus = $screen->dev_mode ? 'approved' : 'not approved';

        $feDeveloperStatus = ($screen->implemented && $screen->integrated) ? 'completed' : 'in_progress';
        $feTesterStatus = $screen->frontend_approved ? 'tested' : 'not_tested';

        $implementedApis = $screen->requestedApis->flatMap->implementedApis;

        if ($implementedApis->isEmpty()) {
            $beDeveloperStatus = 'not_started';
            $beTesterStatus = 'pending';
        } else {
            $statuses = $implementedApis->pluck('status')->unique();
            $beDeveloperStatus = $statuses->contains('tested') ? 'tested' : 'complete';
            $beTesterStatus = $statuses->contains('tested') ? 'tested' : 'pending';
        }

        return response()->json([
            'status' => true,
            'screen_name' => $screen->name,
            'screen_code' => $screen->screen_code,
            'comment' => $screen->comment,
            'ui_development' => [
                'developer_status' => $uiDeveloperStatus,
                'tester_status' => $uiTesterStatus,
                'assigned_to' => $uiDev?->name
            ],
            'frontend_development' => [
                'developer_status' => $feDeveloperStatus,
                'tester_status' => $feTesterStatus,
                'assigned_to' => $frontendDev?->name
            ],
            'backend_development' => [
                'developer_status' => $beDeveloperStatus,
                'tester_status' => $beTesterStatus,
                'assigned_to' => $backendDev?->name
            ]
        ]);
    }

}





