<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMediaController;
use App\Http\Controllers\AddonController;
use App\Http\Controllers\ProductAddonController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:admin')->group(function () {
    Route::apiResource('product-media', ProductMediaController::class);
    Route::get('specific-product-media/{productId}', [ProductMediaController::class, 'getAllMediaForProduct']);
    Route::apiResource('addons', AddonController::class);
    
    // Admin specific route name for products
    Route::apiResource('products', ProductController::class)->names([
        'index' => 'admin.products.index',
        'show' => 'admin.products.show',
        'store' => 'admin.products.store',
        'update' => 'admin.products.update',
        'destroy' => 'admin.products.destroy'
    ]);
    
    Route::apiResource('product-addons', ProductAddonController::class);
});
