<?php

namespace App\Http\Controllers;

use App\Http\Requests\GalleryRequest;
use App\Http\Resources\GalleryResource;
use App\Repositories\GalleryRepositoryInterface;
use App\Services\ImageService; // Make sure to import the ImageService
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    private $galleryRepository;

    public function __construct(GalleryRepositoryInterface $galleryRepository)
    {
        $this->galleryRepository = $galleryRepository;
    }

    public function store(GalleryRequest $request)
    {
        $validated = $request->validated();

        // Check if the 'image' file is present in the request
        if ($request->hasFile('image')) {
            // Use ImageService to upload the file
            $imagePath = ImageService::upload($request->file('image'), 'galleries'); // Assuming 'galleries' folder
        } else {
            return response()->json(['message' => 'No image provided'], 400);
        }

        // Prepare gallery data for storage
        $galleryData = [
            'image_path' => $imagePath,  // The path returned from ImageService
            'galleriable_id' => $validated['galleriable_id'],
            'galleriable_type' => $validated['galleriable_type'],
        ];

        // Save the gallery using the repository
        $gallery = $this->galleryRepository->store($galleryData);

        // Return the newly created gallery resource
        return response()->json(new GalleryResource($gallery), 201);
    }
}
