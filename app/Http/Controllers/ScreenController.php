<?php

namespace App\Http\Controllers;

use App\Repositories\ScreenRepositoryInterface;

class ScreenController extends BaseController
{
    private $repository;

    public function __construct(ScreenRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
