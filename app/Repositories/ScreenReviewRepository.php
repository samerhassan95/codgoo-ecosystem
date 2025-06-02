<?php

namespace App\Repositories;

use App\Models\ScreenReview;
use App\Repositories\Common\CommonRepository;
use App\Http\Requests\ScreenReviewRequest;
use App\Http\Resources\ScreenReviewResource;

class ScreenReviewRepository extends CommonRepository implements ScreenReviewRepositoryInterface
{
    protected const REQUEST = ScreenReviewRequest::class;
    protected const RESOURCE = ScreenReviewResource::class;

    public function model(): string
    {
        return ScreenReview::class;
    }
}
