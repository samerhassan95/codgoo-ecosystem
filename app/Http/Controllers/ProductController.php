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
    // Validate the incoming request
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'price' => 'required|numeric|min:0',
        'note' => 'required|string|max:1000',
        'attachments.*' => 'file|max:10240', // Max 10MB per file
    ]);

    // Exclude attachments from the validated data
    $productData = collect($validatedData)->except('attachments')->toArray();

    // Create the product
    $product = Product::create($productData);

    // Handle attachments using ImageService
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $path = ImageService::upload($file, 'attachments'); // Save the file using ImageService
            $product->attachments()->create([
                'file_path' => $path,
            ]);
        }
    }

    // Return the created product using a resource
    return response()->json(new ProductResource($product->load('attachments')), 201);
}


}
