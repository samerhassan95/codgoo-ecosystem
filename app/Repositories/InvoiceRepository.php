<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\InvoiceRepositoryInterface;
use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;

class InvoiceRepository extends CommonRepository implements InvoiceRepositoryInterface
{
    protected const REQUEST = InvoiceRequest::class;
    protected const RESOURCE = InvoiceResource::class;

    public function model(): string
    {
        return Invoice::class;
    }
    

}

