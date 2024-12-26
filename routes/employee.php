<?php

use App\Http\Controllers\{
    Employee\EmployeeAuthController,
  

};
use Illuminate\Support\Facades\Route;

Route::middleware('auth:employee')->group(function () {
   

    Route::get('profile', [EmployeeAuthController::class, 'getProfile']);

    Route::post('update-profile', [EmployeeAuthController::class, 'updateProfile']);

    Route::post('change-password', [EmployeeAuthController::class, 'changePassword']);
    Route::post('verify-change-phone', [EmployeeAuthController::class, 'verifyChangePhone']);
    Route::post('change-phone-request', [EmployeeAuthController::class, 'changePhoneRequest']);

});
