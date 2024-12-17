<?php

namespace App\Repositories;

use App\Repositories\Common\CommonRepositoryInterface;

interface MilestoneRepositoryInterface extends CommonRepositoryInterface
{
    // Additional custom methods (if needed)
    public function create(array $data);
}
