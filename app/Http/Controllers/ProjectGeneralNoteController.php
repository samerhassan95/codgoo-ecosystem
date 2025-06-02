<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectGeneralNoteRequest;
use App\Http\Resources\ProjectGeneralNoteResource;
use App\Repositories\ProjectGeneralNoteRepositoryInterface;

class ProjectGeneralNoteController extends BaseController
{
    private $repository;

    public function __construct(ProjectGeneralNoteRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }
}
