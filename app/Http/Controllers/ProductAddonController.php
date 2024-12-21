<?php

namespace App\Http\Controllers;

use App\Repositories\ProductAddonRepositoryInterface;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductAddons;

class ProductAddonController extends BaseController
{
    public function __construct(ProductAddonRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'addon_id' => 'required|exists:addons,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $productAddon = ProductAddons::create($validator->validated());

        return response()->json($productAddon, 201);
    }

    public function getAddonsByProject($projectId)
    {
        $product = Product::with('addons')->find($projectId);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $addons = $product->addons;

        return response()->json(['data' => $addons], 200);
    }

}
