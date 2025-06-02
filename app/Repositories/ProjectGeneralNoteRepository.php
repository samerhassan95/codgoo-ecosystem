<?php

namespace App\Repositories;

use App\Models\ProjectGeneralNote;
use App\Repositories\Common\CommonRepository;
use App\Repositories\ProjectGeneralNoteRepositoryInterface;
use App\Http\Requests\ProjectGeneralNoteRequest;
use App\Http\Resources\ProjectGeneralNoteResource;

class ProjectGeneralNoteRepository extends CommonRepository implements ProjectGeneralNoteRepositoryInterface
{
    protected const REQUEST = ProjectGeneralNoteRequest::class;
    protected const RESOURCE = ProjectGeneralNoteResource::class;

    public function model(): string
    {
        return ProjectGeneralNote::class;
    }
}
