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
    private $firebaseService;
    private $notificationRepository;

    public function __construct(ProductRepositoryInterface $repository, FirebaseService $firebaseService, NotificationRepository $notificationRepository)
    {
        parent::__construct($repository);
        $this->firebaseService = $firebaseService;
        $this->notificationRepository = $notificationRepository;
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

        if (!empty($validatedData['addons'])) {
            $product->addons()->attach($validatedData['addons']);
        }

        // **Send Notification to All Clients**
        $clients = Client::whereNotNull('device_token')->get();

        if ($clients->isNotEmpty()) {
            $title = "New Product Added!";
            $message = "Check out our latest product: " . $product->name;

            foreach ($clients as $client) {
                $this->firebaseService->sendNotification($client->device_token, $title, $message);

                $this->notificationRepository->createNotification($client, $title, $message, $client->device_token);
            }
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


    public function show($id)
    {
        $product = Product::with(['media', 'attachments', 'addons'])->find($id);


        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // Manually format the data
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'note' => $product->note,
            'image' => $product->image ? asset($product->image) : null,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
            'media' => $product->media->map(function ($media) {
                return [
                    'id' => $media->id,
                    'file_path' => asset($media->file_path),
                    'type' => $media->type,
                ];
            }),
            'attachments' => $product->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'file_path' => asset($attachment->file_path),
                ];
            }),
            'addons' => $product->addons->map(function ($addon) {
                return [
                    'id' => $addon->id,
                    'name' => $addon->name,
                    'price' => $addon->price,
                ];
            }),
        ];

        return response()->json([
            'status' => true,
            'data' => $data,
        ], 200);
    }


    
}
