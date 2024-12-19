<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Repositories\InvoiceRepositoryInterface;
use Illuminate\Http\Request;


class InvoiceController extends BaseController
{
    private $repository;

    public function __construct(InvoiceRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
