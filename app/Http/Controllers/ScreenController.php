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
                'backend_approved' => $screen->requestedApis
                    ->flatMap(function ($reqApi) {
                        return $reqApi->implementedApis;
                    })
                    ->where('status', 'tested')
                    ->isNotEmpty(),
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
    $isDeveloper = ($user->role !== 'tester' && $reviewType);
dd($reviewType);
    $screens = Screen::whereHas('reviews', function ($query) use ($isDeveloper, $reviewType, $user) {
        $query->where('is_resolved', false);

        if ($isDeveloper) {
            // التعديل هنا: إضافة شرط النوع بشكل منفصل لكل جزء
            $query->where(function ($q) use ($reviewType, $user) {
                $q->where('review_type', $reviewType)
                    ->where(function ($subQ) {
                        $subQ->whereHasMorph('creator', ['App\Models\Employee'], function ($q) {
                            $q->where('role', 'tester');
                        });
                    })
                    ->orWhere(function ($subQ) use ($user) {
                        $subQ->where('review_type', $reviewType)
                            ->where('creator_id', $user->id)
                            ->where('creator_type', get_class($user));
                    });
            });
        } else {
            $query->whereHasMorph('creator', ['App\Models\Employee'], function ($q) {
                $q->where('role', 'tester');
            });
        }
    })
    ->whereHas('task', function ($query) use ($user) {
        $query->whereHas('assignments', function ($qq) use ($user) {
            $qq->where('employee_id', $user->id);
        });
    })
    ->with([
        'task:id,label',
        'requestedApis.implementedApis',
        'reviews' => function ($query) use ($isDeveloper, $reviewType, $user) {
            $query->where('is_resolved', false);

            if ($isDeveloper) {
                // التعديل هنا: نفس المنطق المطبق في whereHas
                $query->where(function ($q) use ($reviewType, $user) {
                    $q->where('review_type', $reviewType)
                        ->where(function ($subQ) {
                            $subQ->whereHasMorph('creator', ['App\Models\Employee'], function ($q) {
                                $q->where('role', 'tester');
                            });
                        })
                        ->orWhere(function ($subQ) use ($user) {
                            $subQ->where('review_type', $reviewType)
                                ->where('creator_id', $user->id)
                                ->where('creator_type', get_class($user));
                        });
                });
            } else {
                $query->whereHasMorph('creator', ['App\Models\Employee'], function ($q) {
                    $q->where('role', 'tester');
                });
            }
            
            $query->with('creator:id,name');
        }
    ])
    ->get()
    ->filter(fn($screen) => $screen->reviews->isNotEmpty())
    ->values()
    ->map(function ($screen) {
        return [
            'screen_id'   => $screen->id,
            'screen_name' => $screen->name,
            'screen_code' => $screen->screen_code,
            'dev_mode'    => $screen->dev_mode,
            'frontend_approved' => $screen->frontend_approved,
            'implemented' => $screen->implemented,
            'integrated'  => $screen->integrated,
            'backend_approved' => $screen->requestedApis
                ->flatMap(function ($reqApi) {
                    return $reqApi->implementedApis;
                })
                ->where('status', 'tested')
                ->isNotEmpty(),
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
        $user = auth()->user();

        $reviewType = $request->get('review_type');
        $validTypes = ['backend', 'frontend', 'ui'];

        if ($reviewType && !in_array($reviewType, $validTypes)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid review type.',
            ], 400);
        }

        $screen = Screen::with(['task:id,label', 'requestedApis'])->find($id);

        if (!$screen) {
            return response()->json([
                'status' => false,
                'message' => 'Screen not found.',
            ], 404);
        }

        $typeToRole = [
            'frontend' => 'front_end',
            'backend'  => 'back_end',
            'ui'       => 'ui_ux',
        ];
        $roleForType = $reviewType ? ($typeToRole[$reviewType] ?? null) : null;

        $reviewQuery = $screen->reviews()->where('is_resolved', false);

        if (!empty($reviewType) && $roleForType) {
            $reviewQuery->where('review_type', $reviewType)
                ->whereHasMorph('creator', ['App\Models\Employee'], function ($q) use ($roleForType) {
                    $q->where(function ($subQ) use ($roleForType) {
                        $subQ->where('role', 'tester')
                            ->orWhere('role', $roleForType);
                    });
                });
        } elseif (!empty($reviewType)) {
            $reviewQuery->where('review_type', $reviewType)
                ->whereHasMorph('creator', ['App\Models\Employee'], function ($q) {
                    $q->where('role', 'tester');
                });
        }

        $reviews = $reviewQuery->with('creator:id,name')->get();

        $screenData = [
            'screen_id'   => $screen->id,
            'screen_name' => $screen->name,
            'screen_code' => $screen->screen_code,
            'dev_mode'    => $screen->dev_mode,
            'task_name'   => $screen->task->label ?? null,
            'comments'    => $reviews->map(function ($review) {
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
            'id' => $screen->id,
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





