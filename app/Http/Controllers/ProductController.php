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
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'note' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'attachments.*' => 'file|max:10240',
            'addons' => 'array',
            'addons.*' => 'exists:addons,id',
        ]);

        $productData = collect($validatedData)->except(['attachments', 'image', 'addons'])->toArray();

        if ($request->hasFile('image')) {
            $imagePath = ImageService::upload($request->file('image'), 'product_images');
            $productData['image'] = $imagePath;
        }

        $product = Product::create($productData);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = ImageService::upload($file, 'attachments');
                $product->attachments()->create(['file_path' => $path]);
            }
        }

        // Attach addons to the product
        if (!empty($validatedData['addons'])) {
            $product->addons()->attach($validatedData['addons']);
        }

        return response()->json(new ProductResource($product->load(['attachments', 'addons'])), 201);
    }



    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'note' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'attachments.*' => 'file|max:10240',
            'addons' => 'array',
            'addons.*' => 'exists:addons,id',
        ]);

        $productData = collect($validatedData)->except(['attachments', 'image', 'addons'])->toArray();

        if ($request->hasFile('image')) {
            if ($product->image) {
                ImageService::delete($product->image);
            }
            $imagePath = ImageService::upload($request->file('image'), 'product_images');
            $productData['image'] = $imagePath;
        }

        $product->update($productData);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = ImageService::upload($file, 'attachments');
                $product->attachments()->create(['file_path' => $path]);
            }
        }

        if (isset($validatedData['addons'])) {
            $product->addons()->sync($validatedData['addons']);
        }

        return response()->json(new ProductResource($product->load(['attachments', 'addons'])), 200);
    }

    
}
