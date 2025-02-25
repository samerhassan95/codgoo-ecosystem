<?php

namespace App\Repositories;

use App\Repositories\Common\CommonRepositoryInterface;

interface TaskRepositoryInterface extends CommonRepositoryInterface
{

    public function create(array $data);
    public function find(int $id);

}
