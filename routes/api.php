<?php

use App\Events\TaskMessageSent;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Client\ClientAuthController;
use App\Http\Controllers\Employee\EmployeeAuthController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\ServicesAppController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\BusinessAppSubscriptionController;// Use the combined controller
// use GPBMetadata\Google\Api\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Str;
// use App\Http\Controllers\BusinessAppSubscriptionController;

use App\Models\TaskDiscussionMessage;
use App\Http\Controllers\TaskDiscussionController;
use App\Models\Employee;



Route::prefix('admin')->group(function () {
    Route::post('register', [AdminAuthController::class, 'register']);
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::post('forgot-password', [AdminAuthController::class, 'forgotPassword']);
Route::post('Marketplace/custom-bundles/{customBundle}/approve', [MarketplaceController::class, 'approveOfflineSubscription']);
    Route::post(
        'business-apps/subscriptions/{subscription}/approve',
        [BusinessAppSubscriptionController::class, 'approve']
    );
});


Route::get('/business-app-plans', [BusinessAppSubscriptionController::class, 'getAllPlans']);
        ////admin approve

Route::prefix('client')->group(function () {
    Route::post('verify-otp', [ClientAuthController::class, 'verifyOtpAndCreateClient']);
    Route::post('forgot-password', [ClientAuthController::class, 'forgotPasswordRequest']);
    Route::post('verify-otp-and-reset-password', [ClientAuthController::class, 'verifyOtp']);
    Route::post('reset-password', [ClientAuthController::class, 'resetPassword']);
    Route::post('login', [ClientAuthController::class, 'login'])->name('login');
    Route::post('register', [ClientAuthController::class, 'register']);
    Route::post('logout', [ClientAuthController::class, 'logout']);

    Route::prefix('Marketplace')->middleware('client')->group(function () {

        Route::get('ServicesApps', [ServicesAppController::class, 'index']);
        Route::get('packages', [MarketplaceController::class, 'indexPackages']);
        Route::get('packages/{package}', [MarketplaceController::class, 'showPackage']);
        Route::get('packagesComparison', [MarketplaceController::class, 'comparison']);

        Route::post('BuildBundle', [MarketplaceController::class, 'storeCustomBundle']);
        Route::post('/bundles/{bundleId}/attach-apps', [MarketplaceController::class, 'attachAppsToBundle']);
        Route::get('Bundle/{bundle}', [MarketplaceController::class, 'showCustomBundle']);
Route::patch('Bundle/{bundlePackageId}', [MarketplaceController::class, 'updateCustomBundle']);
        Route::delete('bundle/{bundle}/applications/{appId}', [MarketplaceController::class, 'destroyApplication']);
        Route::get('launchApp/{app}', [MarketplaceController::class, 'launchApp'])->name('marketplace.launchApp');
        
        Route::get('applications',[MarketplaceController::class, 'mySubscribedApps']);
        Route::post('custom-bundles/{customBundleId}/upload-attachment',[MarketplaceController::class, 'uploadPaymentAttachment'])->name('marketplace.uploadAttachment');
        
        
        
        
        Route::post('bundles/paypal',[MarketplaceController::class, 'subscribeBundleWithPaypal']);
        //////////////////bussiness apps subscription
        Route::post('business-apps/subscribe', [BusinessAppSubscriptionController::class, 'subscribe']);
        Route::post('business-apps/{subscription}/upload-payment', [BusinessAppSubscriptionController::class, 'uploadPayment']);

                    // Route::post('custom-bundles/{customBundleId}/approve',[MarketplaceController::class, 'approveOfflineSubscription']);

    });
});


Route::post('login', [AdminAuthController::class, 'login']);

Route::prefix('employee')->group(function () {
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
// Route::get('payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
// Route::get('payment/cancel', [PaymentController::class, 'paymentCancel'])->name('payment.cancel');
Route::post('opay/callback', [PaymentController::class, 'opayCallback'])->name('opay.callback');


Route::get('/pusher-test', function () {
    $message = TaskDiscussionMessage::create([
        'task_id'     => 8,
        'sender_id'   => 5,
        'sender_type' => Employee::class,
        'message'     => 'Test message from route /pusher-test',
    ]);

    Log::info('Broadcasting test message', ['message_id' => $message->id]);

    broadcast(new TaskMessageSent($message))->toOthers();

    return response()->json([
        'status' => true,
        'message' => 'Pusher test event broadcasted successfully!',
        'data' => $message,
    ]);
});



Route::post('/broadcasting/auth', function (Illuminate\Http\Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:employee');






// In Marketplace routes/web.php (A regular, session-authenticated route)

// Route on the MAIN Laravel Application (Marketplace)
// --- NEW SECURE SSO API ROUTE ---
// This route is called by the Sub-App to validate the token and get user data.
// Route::post('/sso/redeem-token', function (Illuminate\Http\Request $request) {
//     $token = $request->input('token');

//     \Log::info('SSO Token Redemption Request', ['token' => substr($token, 0, 10) . '...']);

//     // 1. Find and validate the token
//     $ssoToken = DB::table('sso_tokens')
//         ->where('token', $token)
//         ->where('expires_at', '>', now())
//         ->first();

//     if (!$ssoToken) {
//         \Log::error('SSO Token Invalid or Expired', ['token' => substr($token, 0, 10) . '...']);
//         return response()->json(['error' => 'Invalid or expired token.'], 401);
//     }

//     \Log::info('SSO Token Found', [
//         'token_type' => $ssoToken->token_type,
//         'client_id' => $ssoToken->client_id
//     ]);

//     // 2. Fetch required client details
//     $client = App\Models\Client::find($ssoToken->client_id);

//     if (!$client) {
//         \Log::error('SSO Client Not Found', ['client_id' => $ssoToken->client_id]);
//         return response()->json(['error' => 'Client not found.'], 404);
//     }

//     // 3. Only delete single-use tokens, NOT profile_access tokens
//     if ($ssoToken->token_type !== 'profile_access') {
//         DB::table('sso_tokens')->where('token', $token)->delete();
//         \Log::info('Single-use SSO token deleted');
//     } else {
//         \Log::info('Profile access token preserved (long-lived)');
//     }

//     // 4. Return client data required for provisioning/login
//     \Log::info('SSO Token Redeemed Successfully', [
//         'client_id' => $client->id,
//         'email' => $client->email
//     ]);

//     return response()->json([
//         'success' => true,
//         'main_app_user_id' => $client->id,
//         'name' => $client->name,
//         'email' => $client->email,
//     ]);
// })->name('sso.token.redeem');
