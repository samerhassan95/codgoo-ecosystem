<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use App\Models\Attachment;
use App\Services\ImageService;
class ProductController extends BaseController
{
    private $repository;
    public function __construct(ProductRepositoryInterface $repository)
    {
        parent::__construct($repository);

    }

    public function store(Request $request)
    {
        // Validate and prepare data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'note' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
            'attachments.*' => 'file|max:10240', // Max 10MB per file
        ]);
    
        // Exclude the image from the data to handle it separately
        $productData = collect($validatedData)->except(['attachments', 'image'])->toArray();
    
        // Handle image upload (if provided)
        if ($request->hasFile('image')) {
            $imagePath = ImageService::upload($request->file('image'), 'product_images');
            $productData['image'] = $imagePath;
        }
    
        // Create the product
        $product = Product::create($productData);
    
        // Handle attachments using ImageService
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = ImageService::upload($file, 'attachments');
                $product->attachments()->create([
                    'file_path' => $path,
                ]);
            }
        }
    
        // Return the created product using a resource, including image
        return response()->json(new ProductResource($product->load('attachments')), 201);
    }

    public function update(Request $request, $id)
    {
        // Find the product
        $product = Product::find($id);
    
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }
    
        // Validate and prepare data
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'note' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate image
            'attachments.*' => 'file|max:10240', // Max 10MB per file
        ]);
    
        // Exclude the image and attachments to handle them separately
        $productData = collect($validatedData)->except(['attachments', 'image'])->toArray();
    
        // Handle image update
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($product->image) {
                ImageService::delete($product->image); // Assuming `ImageService` has a delete method
            }
    
            // Upload the new image
            $imagePath = ImageService::upload($request->file('image'), 'product_images');
            $productData['image'] = $imagePath;
        }
    
        // Update the product data
        $product->update($productData);
    
        // Handle attachment updates (add new attachments if provided)
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = ImageService::upload($file, 'attachments');
                $product->attachments()->create([
                    'file_path' => $path,
                ]);
            }
        }
    
        // Return the updated product using a resource, including attachments
        return response()->json(new ProductResource($product->load('attachments')), 200);
    }
    
}
