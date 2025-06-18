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
        $commentIds = $request->input('comment_ids');

        $updated = ScreenReview::whereIn('id', $commentIds)
            ->whereHasMorph('creator', [Employee::class], function ($query) {
                $query->where('role', 'tester');
            })
            ->update(['is_resolved' => true]);

        return response()->json([
            'status' => true,
            'message' => "$updated comment(s) marked as resolved."
        ]);
    }

}
