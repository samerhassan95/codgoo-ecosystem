<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ScreenReview;
use App\Repositories\ScreenReviewRepositoryInterface;
use Illuminate\Http\Request;

class ScreenReviewController extends BaseController
{
    private $repository;

    public function __construct(ScreenReviewRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    public function markCommentsResolved(Request $request)
    {
        $screenId = $request->input('screen_id');

        $updated = ScreenReview::where('screen_id', $screenId)
            ->where('is_resolved', false)
            ->whereHasMorph('creator', [Employee::class], function ($query) {
                $query->where('role', 'tester');
            })
            ->update(['is_resolved' => true]);

        return response()->json([
            'status' => true,
            'message' => "$updated comment(s) marked as resolved."
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'screen_id'    => 'required|exists:screens,id',
            'comment'      => 'required|string',
            'review_type'  => 'required|in:ui,frontend,backend,mobile',
        ]);

        if (auth('employee')->check()) {
            $user = auth('employee')->user();
            $creatorType = \App\Models\Employee::class;
        } elseif (auth('admin')->check()) {
            $user = auth('admin')->user();
            $creatorType = \App\Models\Admin::class;
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized user.',
            ], 401);
        }

        $review = ScreenReview::create([
            'screen_id'    => $request->screen_id,
            'comment'      => $request->comment,
            'review_type'  => $request->review_type,
            'creator_type' => $creatorType,
            'creator_id'   => $user->id,
            'is_resolved'  => false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Comment added successfully.',
            'data' => [
                'id' => $review->id,
                'screen_id' => $review->screen_id,
                'comment' => $review->comment,
                'review_type' => $review->review_type,
                'creator_name' => $user->name,
                'is_resolved' => $review->is_resolved,
                'created_at' => $review->created_at->toDateTimeString(),
            ],
        ]);
    }

}
