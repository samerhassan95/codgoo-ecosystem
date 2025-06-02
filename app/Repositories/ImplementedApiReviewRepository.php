<?php

namespace App\Repositories;

use App\Models\ImplementedApiReview;
use App\Http\Requests\ImplementedApiReviewRequest;
use App\Http\Resources\ImplementedApiReviewResource;
use App\Repositories\Common\CommonRepository;

class ImplementedApiReviewRepository extends CommonRepository implements ImplementedApiReviewRepositoryInterface
{
    protected const REQUEST = ImplementedApiReviewRequest::class;
    protected const RESOURCE = ImplementedApiReviewResource::class;

    public function model(): string
    {
        return ImplementedApiReview::class;
    }
}
