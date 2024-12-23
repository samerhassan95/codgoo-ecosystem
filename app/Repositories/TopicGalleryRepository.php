<?php

namespace App\Repositories;

use App\Models\TopicGallery;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\TopicGalleryRepositoryInterface;
use App\Http\Requests\TopicGalleryRequest;
use App\Http\Resources\TopicGalleryResource;

class TopicGalleryRepository extends CommonRepository implements TopicGalleryRepositoryInterface
{
    protected const REQUEST = TopicGalleryRequest::class;
    protected const RESOURCE = TopicGalleryResource::class;

    public function model(): string
    {
        return TopicGallery::class;
    }
    

}

