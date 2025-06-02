<?php

namespace App\Http\Controllers;

use App\Http\Requests\RemoteWorkRequestRequest;
use App\Http\Resources\RemoteWorkRequestResource;
use App\Models\RemoteWorkRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Repositories\RemoteWorkRequestRepositoryInterface;
class RemoteWorkRequestController extends BaseController
{
    private $repository;

    public function __construct(RemoteWorkRequestRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
