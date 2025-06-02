<?php

namespace App\Repositories;

use App\Models\Address;
use App\Repositories\Common\CommonRepository;
use App\Repositories\AddressRepositoryInterface;
use App\Http\Requests\AddressRequest;
use App\Http\Resources\AddressResource;

class AddressRepository extends CommonRepository implements AddressRepositoryInterface
{
    protected const REQUEST = AddressRequest::class;
    protected const RESOURCE = AddressResource::class;

    public function model(): string
    {
        return Address::class;
    }
}
