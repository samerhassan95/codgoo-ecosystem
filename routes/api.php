<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Client\ClientAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function() {
    Route::post('register', [AdminAuthController::class, 'register']);
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::post('forgot-password', [AdminAuthController::class, 'forgotPassword']);
});

Route::prefix('client')->group(function() {
    Route::post('register', [ClientAuthController::class, 'register']);
    // Route::post('login', [ClientAuthController::class, 'login']);
    Route::post('logout', [ClientAuthController::class, 'logout']);
    Route::post('verify-otp', [ClientAuthController::class, 'verifyOtpAndCreateClient']);
    Route::post('forgot-password', [ClientAuthController::class, 'forgotPasswordRequest']);
    Route::post('verify-otp-and-reset-password', [ClientAuthController::class, 'verifyOtp']);
    Route::post('reset-password', [ClientAuthController::class, 'resetPassword']);

});


Route::post('login', [AdminAuthController::class, 'login']);


