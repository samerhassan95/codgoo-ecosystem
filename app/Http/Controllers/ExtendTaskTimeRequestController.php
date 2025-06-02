<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExtendTaskTimeRequestRequest;
use App\Http\Resources\ExtendTaskTimeRequestResource;
use App\Models\ExtendTaskTimeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Repositories\ExtendTaskTimeRequestRepositoryInterface;

class ExtendTaskTimeRequestController extends BaseController
{
    private $repository;

    public function __construct(ExtendTaskTimeRequestRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
 
}
