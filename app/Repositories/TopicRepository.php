<?php

namespace App\Repositories;

use App\Models\Topic;
use App\Models\TopicGallery;
use Illuminate\Support\Facades\DB;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use App\Repositories\TopicRepositoryInterface;
use App\Http\Requests\TopicRequest;
use App\Http\Resources\TopicResource;
use Illuminate\Support\Facades\Validator;
use App\Services\ImageService; // Add ImageService import

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
        // Manually create an instance of TopicRequest for validation
        $topicRequest = new TopicRequest();

        // Manually validate the request using the TopicRequest rules
        $validator = Validator::make($request->all(), $topicRequest->rules());

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Begin a transaction to ensure both the topic and gallery are saved together
        DB::beginTransaction();

        try {
            // Create the topic using validated data
            $topic = Topic::create($validator->validated());

            // If there are gallery images, handle the file upload and save them
            if ($request->has('gallery') && is_array($request->gallery)) {
                $galleryData = [];

                foreach ($request->gallery as $image) {
                    // Use ImageService to upload the image
                    $imagePath = ImageService::upload($image, 'galleries'); // Adjust the folder name if needed
                    $galleryData[] = [
                        'topic_id' => $topic->id,
                        'image_path' => $imagePath,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Insert gallery data into the database
                TopicGallery::insert($galleryData);
            }

            // Commit the transaction
            DB::commit();

            // Return a success response with the topic and its galleries
            return response()->json([
                'message' => 'Topic and gallery created successfully.',
                'data' => new TopicResource($topic),
            ], 201);

        } catch (\Exception $e) {
            // If an error occurs, roll back the transaction
            DB::rollBack();

            // Return error response
            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Manually create an instance of TopicRequest for validation
        $topicRequest = new TopicRequest();

        // Manually validate the request using the TopicRequest rules
        $validator = Validator::make($request->all(), $topicRequest->rules());

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Begin a transaction to ensure both the topic and gallery are updated together
        DB::beginTransaction();

        try {
            // Find the topic by ID
            $topic = Topic::findOrFail($id);
            $topic->update($validator->validated()); // Update the topic

            // Update galleries if new images are provided
            if ($request->has('gallery') && is_array($request->gallery)) {
                // Delete existing galleries
                $topic->galleries()->delete();

                $galleryData = [];

                foreach ($request->gallery as $image) {
                    $imagePath = $image->store('public/galleries'); // Store image
                    $galleryData[] = [
                        'topic_id' => $topic->id,
                        'image_path' => $imagePath,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Insert new gallery data
                TopicGallery::insert($galleryData);
            }

            // Commit the transaction
            DB::commit();

            // Return a success response with the updated topic
            return response()->json([
                'message' => 'Topic and gallery updated successfully.',
                'data' => new TopicResource($topic),
            ], 200);

        } catch (\Exception $e) {
            // Rollback transaction in case of error
            DB::rollBack();

            // Return error response
            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
