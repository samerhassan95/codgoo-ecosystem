<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestedApiRequest;
use App\Http\Resources\RequestedApiResource;
use App\Repositories\RequestedApiRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestedApiController extends BaseController
{
    private $repository;

    public function __construct(RequestedApiRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
