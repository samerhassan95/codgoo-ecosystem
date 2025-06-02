<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImplementedApiRequest;
use App\Http\Resources\ImplementedApiResource;
use App\Repositories\ImplementedApiRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImplementedApiController extends BaseController
{
    private $repository;

    public function __construct(ImplementedApiRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

}
