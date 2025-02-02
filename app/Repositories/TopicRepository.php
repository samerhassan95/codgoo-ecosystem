<?php

namespace App\Repositories;

use App\Models\Topic;
use App\Models\Gallery;
use Illuminate\Support\Facades\DB;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\TopicRepositoryInterface;
use App\Http\Requests\TopicRequest;
use App\Http\Resources\TopicResource;
use Illuminate\Support\Facades\Validator;
use App\Services\ImageService; 

class TopicRepository extends CommonRepository implements TopicRepositoryInterface
{
    protected const REQUEST = TopicRequest::class;
    protected const RESOURCE = TopicResource::class;

    public function model(): string
    {
        return Topic::class;
    }

    public function store(Request $request)
{
    $topicRequest = new TopicRequest();

    $validator = Validator::make($request->all(), $topicRequest->rules());

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422);
    }

    DB::beginTransaction();

    try {
        // Create the topic
        $topic = Topic::create($validator->validated());

        // Handle gallery if images are provided
        if ($request->has('gallery') && is_array($request->gallery)) {
            $galleryData = [];

            foreach ($request->gallery as $image) {
                // Upload image
                $imagePath = ImageService::upload($image, 'galleries');

                // Adjust the gallery data to use polymorphic fields
                $galleryData[] = [
                    'galleriable_id' => $topic->id, // Use the topic ID for galleriable_id
                    'galleriable_type' => Topic::class, // The model type for polymorphic relation
                    'image_path' => $imagePath,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert gallery data into the galleries table
            Gallery::insert($galleryData);
        }

        DB::commit();

        return response()->json([
            'message' => 'Topic and gallery created successfully.',
            'data' => new TopicResource($topic),
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Something went wrong.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    public function update(int $id, array $data)
    {
        $topicRequest = new TopicRequest();
    
        $validator = Validator::make($data, $topicRequest->rules());
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        DB::beginTransaction();
    
        try {
            $topic = Topic::findOrFail($id);
            $topic->update($validator->validated());
    
            if (isset($data['gallery']) && is_array($data['gallery'])) {
                $topic->galleries()->delete();
    
                $galleryData = [];
    
                foreach ($data['gallery'] as $image) {
                    $imagePath = ImageService::upload($image, 'galleries');
                    $galleryData[] = [
                        'topic_id' => $topic->id,
                        'image_path' => $imagePath,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
    
                Gallery::insert($galleryData);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Topic and gallery updated successfully.',
                'data' => new TopicResource($topic),
            ], 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
}
