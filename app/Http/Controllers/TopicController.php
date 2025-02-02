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

  

    public function getTopicsBySection(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|integer|in:' . implode(',', array_keys(\App\Enum\SectionEnum::getList())), 
        ]);
    
        $topics = Topic::where('section_id', $validated['section_id'])->get();
    
        return response()->json([
            'success' => true,
            'data' => TopicResource::collection($topics),
        ]);
    }
    
    

}
