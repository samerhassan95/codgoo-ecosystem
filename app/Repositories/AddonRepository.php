<?php

namespace App\Repositories;

use App\Models\Addon;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\AddonRepositoryInterface;
use App\Http\Requests\AddonRequest;
use App\Http\Resources\AddonResource;

class AddonRepository extends CommonRepository implements AddonRepositoryInterface
{
    protected const REQUEST = AddonRequest::class;
    protected const RESOURCE = AddonResource::class;

    public function model(): string
    {
        return Addon::class;
    }

    public function create(array $data)
    {
        return Addon::create($data);
    }
    
    public function find(int $id)
    {
        return $this->getModel()->findOrFail($id);
    }

    public function update($id, array $data)
    {
        $addon = $this->find($id);
        $addon->update($data);
        return $addon;
    }
    
    

}