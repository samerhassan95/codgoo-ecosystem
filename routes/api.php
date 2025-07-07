<?php

use App\Events\TaskMessageSent;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Client\ClientAuthController;
use App\Http\Controllers\Employee\EmployeeAuthController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\NotificationController;

use App\Models\TaskDiscussionMessage;
use App\Models\Employee;

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

Route::prefix('employee')->group(function() {
    Route::post('register', [EmployeeAuthController::class, 'register']);
    Route::post('login', [EmployeeAuthController::class, 'login']);
    Route::post('logout', [EmployeeAuthController::class, 'logout']);
    Route::post('verify-otp', [EmployeeAuthController::class, 'verifyOtpAndCreateEmployee']);
    Route::post('forgot-password', [EmployeeAuthController::class, 'forgotPasswordRequest']);
    Route::post('verify-otp-and-reset-password', [EmployeeAuthController::class, 'verifyOtp']);
    Route::post('reset-password', [EmployeeAuthController::class, 'resetPassword']);

});

// Route::get('test-email', function () {
//     Mail::raw('This is a test email from Laravel.', function ($message) {
//         $message->to('fatmamohamed2101@gmail.com')
//                 ->subject('Test Email');
//     });

//     return 'Email has been sent!';
// });
Route::post('send-chat-notification', [NotificationController::class, 'sendChatNotification']);
Route::get('payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::get('payment/cancel', [PaymentController::class, 'paymentCancel'])->name('payment.cancel');
Route::post('opay/callback', [PaymentController::class, 'opayCallback'])->name('opay.callback');


Route::get('/pusher-test', function () {
    $message = TaskDiscussionMessage::create([
        'task_id'     => 8,
        'sender_id'   => 5,
        'sender_type' => Employee::class,
        'message'     => 'Test message from route /pusher-test',
    ]);

    \Log::info('Broadcasting test message', ['message_id' => $message->id]);

    broadcast(new TaskMessageSent($message))->toOthers();

    return response()->json([
        'status' => true,
        'message' => 'Pusher test event broadcasted successfully!',
        'data' => $message,
    ]);
});

