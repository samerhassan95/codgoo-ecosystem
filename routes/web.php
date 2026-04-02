<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthenticationController as AdminAuth;
use App\Http\Controllers\Client\AuthenticationController as ClientAuth;


use App\Http\Controllers\PaymentController;

Route::get('paypal/success/{payment}', [PaymentController::class, 'paypalSuccess'])->name('paypal.success');

Route::get('paypal/cancel/{payment}', [PaymentController::class, 'paypalCancel'])->name('paypal.cancel');


Route::get('/', function () {
    return view('welcome');
});


