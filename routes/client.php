
<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\Client\ClientAuthController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:client')->group(function () {
    Route::apiResource('projects', ProjectController::class);
    
    // Client specific route name for products
    Route::apiResource('products', ProductController::class)->only(['index', 'show'])->names([
        'index' => 'client.products.index',
        'show' => 'client.products.show',
    ]);
    
    // Profile routes
    Route::get('profile', [ClientAuthController::class, 'getProfile']);
    Route::put('update-profile', [ClientAuthController::class, 'updateProfile']);
});
