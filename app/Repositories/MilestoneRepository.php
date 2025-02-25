<?php

namespace App\Repositories;

use App\Models\Milestone;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\MilestoneRepositoryInterface;
use App\Http\Requests\MilestoneRequest;
use App\Http\Resources\MilestoneResource;

class MilestoneRepository extends CommonRepository implements MilestoneRepositoryInterface
{
    protected const REQUEST = MilestoneRequest::class;
    protected const RESOURCE = MilestoneResource::class;

    public function model(): string
    {
        return Milestone::class;
    }
    
    public function create(array $data)
    {
        return Milestone::create($data);
    }

    public function find(int $id)
{
    return Milestone::find($id);
}

}