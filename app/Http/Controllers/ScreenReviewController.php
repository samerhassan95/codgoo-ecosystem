<?php

namespace App\Http\Controllers;

use App\Repositories\ScreenReviewRepositoryInterface;

class ScreenReviewController extends BaseController
{
    private $repository;

    public function __construct(ScreenReviewRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
