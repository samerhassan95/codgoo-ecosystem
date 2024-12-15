<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Client\ClientAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMediaController;
use App\Http\Controllers\AddonController;
use App\Http\Controllers\ProductAddonController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function() {
    Route::post('register', [AdminAuthController::class, 'register']);
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::post('forgot-password', [AdminAuthController::class, 'forgotPassword']);
});

Route::prefix('client')->group(function() {
    Route::post('register', [ClientAuthController::class, 'register']);
    Route::post('login', [ClientAuthController::class, 'login']);
    Route::post('logout', [ClientAuthController::class, 'logout']);
    Route::post('forgot-password', [ClientAuthController::class, 'forgotPassword']);
    Route::post('verify-otp', [ClientAuthController::class, 'verifyOtpAndCreateClient']);
});

Route::middleware('auth:admin')->group(function () {
    Route::apiResource('product-media', ProductMediaController::class);
    Route::get('specific-product-media/{productId}', [ProductMediaController::class, 'getAllMediaForProduct']);
    Route::apiResource('addons', AddonController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('product-addons', ProductAddonController::class);
    // Route::apiResource('projects', ProjectController::class);


});


Route::middleware('auth:client')->group(function () {

    Route::apiResource('projects', ProjectController::class);


});