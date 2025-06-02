<?php

namespace App\Repositories;

use App\Models\Achievement;
use App\Http\Requests\AchievementRequest;
use App\Http\Resources\AchievementResource;
use App\Repositories\Common\CommonRepository;

class AchievementRepository extends CommonRepository implements AchievementRepositoryInterface
{
    protected const REQUEST = AchievementRequest::class;
    protected const RESOURCE = AchievementResource::class;

    public function model(): string
    {
        return Achievement::class;
    }
}
