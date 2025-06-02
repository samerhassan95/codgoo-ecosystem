<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoneyRequestRequest;
use App\Http\Resources\MoneyRequestResource;
use App\Models\MoneyRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Repositories\MoneyRequestRepositoryInterface;

class MoneyRequestController extends BaseController
{
    private $repository;

    public function __construct(MoneyRequestRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
