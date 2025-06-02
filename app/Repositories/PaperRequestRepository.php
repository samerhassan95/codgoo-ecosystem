<?php

namespace App\Repositories;

use App\Models\PaperRequest;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\PaperRequestRepositoryInterface;
use App\Http\Requests\PaperRequestRequest;
use App\Http\Resources\PaperRequestResource;

class PaperRequestRepository extends CommonRepository implements PaperRequestRepositoryInterface
{
    protected const REQUEST = PaperRequestRequest::class;
    protected const RESOURCE = PaperRequestResource::class;

    public function model(): string
    {
        return PaperRequest::class;
    }
    
   
}