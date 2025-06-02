<?php

namespace App\Http\Controllers;

use App\Repositories\AchievementRepositoryInterface;

class AchievementController extends BaseController
{
    public function __construct(AchievementRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
