<?php

namespace App\Http\Controllers;

use App\Repositories\AddressRepositoryInterface;

class AddressController extends BaseController
{
    private $repository;

    public function __construct(AddressRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
