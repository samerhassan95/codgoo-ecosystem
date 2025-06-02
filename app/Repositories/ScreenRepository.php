<?php

namespace App\Repositories;

use App\Models\Screen;
use App\Repositories\Common\CommonRepository;
use App\Http\Requests\ScreenRequest;
use App\Http\Resources\ScreenResource;

class ScreenRepository extends CommonRepository implements ScreenRepositoryInterface
{
    protected const REQUEST = ScreenRequest::class;
    protected const RESOURCE = ScreenResource::class;

    public function model(): string
    {
        return Screen::class;
    }
}
