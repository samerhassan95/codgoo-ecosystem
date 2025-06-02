<?php

namespace App\Repositories;

use App\Models\MoneyRequest;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\MoneyRequestRepositoryInterface;
use App\Http\Requests\MoneyRequestRequest;
use App\Http\Resources\MoneyRequestResource;

class MoneyRequestRepository extends CommonRepository implements MoneyRequestRepositoryInterface
{
    protected const REQUEST = MoneyRequestRequest::class;
    protected const RESOURCE = MoneyRequestResource::class;

    public function model(): string
    {
        return MoneyRequest::class;
    }
    
   
}