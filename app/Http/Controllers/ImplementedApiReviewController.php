<?php

namespace App\Http\Controllers;

use App\Repositories\ImplementedApiReviewRepositoryInterface;

class ImplementedApiReviewController extends BaseController
{
    public function __construct(ImplementedApiReviewRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
