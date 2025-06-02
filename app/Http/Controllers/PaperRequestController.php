<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaperRequestRequest;
use App\Http\Resources\PaperRequestResource;
use App\Models\PaperRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Repositories\PaperRequestRepositoryInterface;
class PaperRequestController extends BaseController
{
    private $repository;

    public function __construct(PaperRequestRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
