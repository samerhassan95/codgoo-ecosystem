<?php

namespace App\Repositories;

use App\Models\ProjectAddons;
use App\Repositories\Common\CommonRepository;
use App\Repositories\ProjectAddonRepositoryInterface;
use App\Http\Resources\ProjectAddonResource;
use App\Http\Requests\ProjectAddonRequest;

class ProjectAddonRepository extends CommonRepository implements ProjectAddonRepositoryInterface
{
    protected const RESOURCE = ProjectAddonResource::class;
    protected const REQUEST = ProjectAddonRequest::class;

    public function model(): string
    {
        return ProjectAddons::class;
    }
}
