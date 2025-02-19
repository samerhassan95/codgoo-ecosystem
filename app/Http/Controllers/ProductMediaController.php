<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductMediaRequest;
use App\Http\Resources\ProductMediaResource;
use App\Repositories\ProductMediaRepositoryInterface;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductMedia;

class ProductMediaController extends BaseController
{
    private $repository;

    public function __construct(ProductMediaRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'file_path' => 'required|file',
            'type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->toArray()], 422);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('file_path')) {
            $filePath = ImageService::upload($request->file('file_path'), 'product_media');
            $validatedData['file_path'] = $filePath;
        } else {
            return response()->json(['message' => 'No file uploaded.'], 400);
        }

        $productMedia = $this->repository->create($validatedData);

        return new ProductMediaResource($productMedia);
    }

    /**
     * Get all media for a specific product.
     *
     * @param  int  $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllMediaForProduct($productId)
    {
        // Get all media related to the product
        $media = ProductMedia::where('product_id', $productId)->get();

        // If no media found, return a message
        if ($media->isEmpty()) {
            return response()->json(['message' => 'No media found for this product.'], 404);
        }

        // Return the media data as a resource
        return ProductMediaResource::collection($media);
    }

    /**
     * Update a specific media record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Find the media record by ID
        $media = ProductMedia::find($id);

        // If media is not found, return a 404 response
        if (!$media) {
            return response()->json(['message' => 'Media not found.'], 404);
        }

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'file_path' => 'nullable|file', // Optional file upload
            'type' => 'nullable|string', // Optional type update
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->toArray()], 422);
        }

        // Prepare the data for update
        $validatedData = $validator->validated();

        // Handle file replacement if a new file is uploaded
        if ($request->hasFile('file_path')) {
            // Delete the old file if it exists
            if ($media->file_path && \Storage::disk('public')->exists($media->file_path)) {
                \Storage::disk('public')->delete($media->file_path);
            }

            // Store the new file
            $validatedData['file_path'] = $request->file('file_path')->store('product_media', 'public');
        }

        // Update the media record
        $media->update($validatedData);

        // Return the updated media record as a resource
        return new ProductMediaResource($media);
    }

}
