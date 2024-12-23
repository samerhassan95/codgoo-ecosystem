<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopicRequest;
use App\Http\Resources\TopicResource;
use App\Models\Topic;
use App\Models\Project;
use App\Repositories\TopicRepositoryInterface;
use Illuminate\Http\Request;


class TopicController extends BaseController
{
    private $repository;

    public function __construct(TopicRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

}
