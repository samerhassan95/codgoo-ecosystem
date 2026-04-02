<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CustomBundle;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Repositories\Client\ClientRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Services\ImageService;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class ClientAuthController extends Controller
{
    protected $clientRepo;

    public function __construct(ClientRepositoryInterface $clientRepo)
    {
        $this->clientRepo = $clientRepo;
    }

    // ----------------- REGISTER -----------------
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'unique:clients,phone', function ($attr, $val, $fail) {
                if (Admin::where('phone', $val)->exists()) {
                    $fail("This phone number is already registered as an admin.");
                }
            }],
            'username' => ['required', 'unique:clients,username', function ($attr, $val, $fail) {
                if (Admin::where('username', $val)->exists()) {
                    $fail("This username is already registered as an admin.");
                }
            }],
            'password' => 'required|min:6|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'email' => 'required|email|unique:clients,email',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'device_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 422);
        }

        $generatedName = ucwords(str_replace(['_', '-'], ' ', $request->username));

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = ImageService::upload($request->file('photo'), 'client_photos');
        }

        $client = Client::create([
            'username' => $request->username,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'name' => $generatedName,
            'email' => $request->email,
            'photo' => $photoPath,
            'company_name' => $request->company_name,
            'website' => $request->website,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'device_token' => $request->device_token,
        ]);

        $otp = rand(1000, 9999);
        Cache::put('otp_' . $client->phone, $otp, now()->addMinutes(10));

        $message = "OTP sent successfully to your email, please verify.";

        try {
            Mail::to($client->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            Log::error("OTP Email failed for client {$client->id}: " . $e->getMessage());
            $message = "Registration successful, but OTP email failed. Use verification endpoint.";
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'otp' => $otp,
            'data' => ['phone' => $client->phone],
        ], 201);
    }

    // ----------------- LOGIN -----------------
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 422);
        }

        $email = $request->email;
        $loginType = filter_var($email, FILTER_VALIDATE_EMAIL) ? 'email' : (is_numeric($email) ? 'phone' : 'username');
        $client = Client::where($loginType, $email)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json([
                "status" => false,
                'code' => 401,
                'message' => 'Invalid credentials.',
                'data' => null,
            ], 401);
        }

        if ($request->filled('device_token')) {
            $client->device_token = $request->device_token;
            $client->save();
        }

        $claims = ['type' => 'client', 'role' => $client->role ?? 'client'];
        $token = JWTAuth::claims($claims)->fromUser($client);

        // Fetch bundles with dynamic expiration
        $activeBundles = CustomBundle::with(['bundlePackage', 'price', 'applications'])
            ->where('customer_id', $client->id)
            ->where('status', 'active')
            ->get()
            ->filter(fn($bundle) => $bundle->expires_at && now()->lt($bundle->expires_at));

        return response()->json([
            'status' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'role' => $client->role ?? 'client',
                'phone' => $client->phone],// Changed this line            ],
            'subscriptions' => $activeBundles->map(fn($bundle) => [
                'id' => $bundle->id,
                'bundle_package_id' => $bundle->bundle_package_id,
                'bundle_name' => $bundle->bundlePackage->name ?? null,
                'status' => $bundle->status,
                'expires_at' => $bundle->expires_at,
                'applications' => $bundle->applications->map(fn($app) => [
                    'id' => $app->id,
                    'name' => $app->name,
                    'slug' => $app->slug,
                    'type' => $app->type,
                    'is_external' => $app->is_external,
                    'launch_url' => $app->pivot->external_profile_url ?? $app->app_url ?? null,
                ]),
            ]),
        ]);
    }

    // ----------------- VERIFY OTP AND LOGIN -----------------
    public function verifyOtpAndCreateClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
            'phone' => 'required|exists:clients,phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 402);
        }

        $storedOtp = Cache::get('otp_' . $request->phone);

        if (!$storedOtp || $storedOtp != $request->otp) {
            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => 'Invalid or expired OTP. Please try again.',
                'data' => null,
            ], 402);
        }

        $client = Client::where('phone', $request->phone)->first();

        // token includes type & role
        $claims = [
            'type' => 'client',
            'role' => $client->role ?? 'client',
        ];
        $token = JWTAuth::claims($claims)->fromUser($client);

        Cache::forget('otp_' . $request->phone);

        return $this->respondWithToken($client, $token, 'OTP verified successfully.');
    }

    // ----------------- GET PROFILE -----------------
    public function getProfile(Request $request)
    {
        try {
            $client = $this->clientRepo->getAuthenticatedClient();
        } catch (\Exception $e) {
            $client = null;
        }

        if (!$client) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'data' => null], 401);
        }

    if ($client->photo) {
        $client->photo = asset($client->photo);
    }
        return response()->json([
            'status' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'client' => $client->makeHidden(['password', 'remember_token']),
                'token' => JWTAuth::fromUser($client),
                'type' => 'client'
            ],
        ]);
    }

    // ----------------- UPDATE PROFILE -----------------
    public function updateProfile(Request $request)
    {
        try {
            $client = $this->clientRepo->getAuthenticatedClient();
        } catch (\Exception $e) {
            $client = null;
        }

        if (!$client) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'data' => null], 401);
        }

        $request->validate([
            'username' => 'sometimes|required|string|max:255|unique:clients,username,' . $client->id,
            'email' => 'sometimes|required|email|max:255|unique:clients,email,' . $client->id,
            'phone' => 'sometimes|required|string|max:255|unique:clients,phone,' . $client->id,
            'name' => 'sometimes|required|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        $photoPath = $request->hasFile('photo') ? (new ImageService())->upload($request->file('photo'), 'client_photos') : null;

        $client->update([
            'username' => $request->username ?? $client->username,
            'name' => $request->name ?? $client->name,
            'email' => $request->email ?? $client->email,
            'phone' => $request->phone ?? $client->phone,
            'photo' => $photoPath ?? $client->photo,
            'company_name' => $request->company_name ?? $client->company_name,
            'website' => $request->website ?? $client->website,
            'address' => $request->address ?? $client->address,
            'city' => $request->city ?? $client->city,
            'country' => $request->country ?? $client->country,
        ]);

        // return refreshed token
        $claims = [
            'type' => 'client',
            'role' => $client->role ?? 'client',
        ];
        $token = JWTAuth::claims($claims)->fromUser($client);

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'client' => $client->makeHidden(['password', 'remember_token']),
                'token' => $token,
                'type' => 'client'
            ],
        ]);
    }

    // ----------------- CHANGE PASSWORD -----------------
    // Replaced old method with current-password check and confirmation
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'data' => null], 422);
        }

        try {
            $client = $this->clientRepo->getAuthenticatedClient();
        } catch (\Exception $e) {
            $client = null;
        }

        if (!$client) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'data' => null], 401);
        }

        if (!Hash::check($request->current_password, $client->password)) {
            return response()->json(['status' => false, 'message' => 'Current password is incorrect.', 'data' => null], 401);
        }

        $client->password = Hash::make($request->new_password);
        $client->save();

        return response()->json(['status' => true, 'message' => 'Password changed successfully.', 'data' => null], 200);
    }

    // ----------------- CHANGE PHONE -----------------
    public function changePhoneRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_phone' => 'required|unique:clients,phone',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'data' => null], 402);
        }

        try {
            $client = $this->clientRepo->getAuthenticatedClient();
        } catch (\Exception $e) {
            $client = null;
        }

        if (!$client) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'data' => null], 401);
        }

        $otp = rand(1000, 9999);
        Cache::put('otp_change_phone_' . $client->id, ['otp' => $otp, 'new_phone' => $request->new_phone], now()->addMinutes(10));
        Mail::to($client->email)->send(new OtpMail($otp));

        return response()->json(['status' => true, 'message' => 'OTP sent to your email.', 'data' => null], 200);
    }

    public function verifyChangePhone(Request $request)
    {
        $validator = Validator::make($request->all(), ['otp' => 'required|numeric']);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'data' => null], 402);
        }

        try {
            $client = $this->clientRepo->getAuthenticatedClient();
        } catch (\Exception $e) {
            $client = null;
        }

        if (!$client) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'data' => null], 401);
        }

        $cachedData = Cache::get('otp_change_phone_' . $client->id);
        if (!$cachedData || $cachedData['otp'] != $request->otp) {
            return response()->json(['status' => false, 'message' => 'Invalid or expired OTP.', 'data' => null], 402);
        }

        $client->phone = $cachedData['new_phone'];
        $client->save();
        Cache::forget('otp_change_phone_' . $client->id);

        return response()->json(['status' => true, 'message' => 'Phone number updated successfully.', 'data' => ['phone' => $client->phone]], 200);
    }

    // ----------------- CHANGE EMAIL (initiate / verify) -----------------
    public function changeEmail(Request $request)
    {
        try {
            $client = $this->clientRepo->getAuthenticatedClient();
        } catch (\Throwable $e) {
            $client = null;
        }

        if (!$client) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'data' => null], 401);
        }

        // Verification step
        if ($request->filled('otp')) {
            $validator = Validator::make($request->all(), [
                'otp' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'data' => null], 422);
            }

            $cached = Cache::get('otp_change_email_' . $client->id);
            if (!$cached || !isset($cached['otp']) || $cached['otp'] != $request->otp) {
                return response()->json(['status' => false, 'message' => 'Invalid or expired OTP.', 'data' => null], 402);
            }

            $client->email = $cached['new_email'];
            $client->save();
            Cache::forget('otp_change_email_' . $client->id);

            $claims = ['type' => 'client', 'role' => $client->role ?? 'client'];
            $token = JWTAuth::claims($claims)->fromUser($client);

            return response()->json([
                'status' => true,
                'message' => 'Email updated successfully.',
                'data' => [
                    'client' => $client->makeHidden(['password', 'remember_token']),
                    'token' => $token,
                    'type' => 'client'
                ],
            ]);
        }

        // Initiate change: require new_email + password
        $validator = Validator::make($request->all(), [
            'new_email' => 'required|email|unique:clients,email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'data' => null], 422);
        }

        if (!Hash::check($request->password, $client->password)) {
            return response()->json(['status' => false, 'message' => 'Invalid password.', 'data' => null], 401);
        }

        $otp = rand(1000, 9999);
        Cache::put('otp_change_email_' . $client->id, ['otp' => $otp, 'new_email' => $request->new_email], now()->addMinutes(10));

        try {
            Mail::to($request->new_email)->send(new OtpMail($otp));
            $message = 'Verification code sent to the new email. Please verify to complete the change.';
        } catch (\Throwable $e) {
            Log::error("Change email OTP send failed for client {$client->id}: " . $e->getMessage());
            $message = 'Verification code generated, but sending email failed. Use verification endpoint with the OTP.';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'otp' => config('app.env') !== 'production' ? $otp : null,
            'data' => null
        ], 200);
    }

    // ----------------- CHANGE PROFILE (name and/or photo) -----------------
    public function changeProfile(Request $request)
    {
        try {
            $client = $this->clientRepo->getAuthenticatedClient();
        } catch (\Throwable $e) {
            $client = null;
        }

        if (!$client) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'data' => null], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'data' => null], 422);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = ImageService::upload($request->file('photo'), 'client_photos');
        }

        $client->name = $request->filled('name') ? $request->name : $client->name;
        $client->photo = $photoPath ?? $client->photo;
        $client->save();

        $claims = ['type' => 'client', 'role' => $client->role ?? 'client'];
        $token = JWTAuth::claims($claims)->fromUser($client);

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'client' => $client->makeHidden(['password', 'remember_token']),
                'token' => $token,
                'type' => 'client'
            ],
        ], 200);
    }

    // ----------------- FORGOT PASSWORD -----------------
    public function forgotPasswordRequest(Request $request)
    {
        $validator = Validator::make($request->all(), ['phone' => 'required|exists:clients,phone']);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Phone number does not exist.', 'data' => null], 404);
        }

        $client = Client::where('phone', $request->phone)->first();
        $otp = rand(1000, 9999);
        Cache::put('otp_' . $client->phone, $otp, now()->addMinutes(10));
        Mail::to($client->email)->send(new OtpMail($otp));

        return response()->json(['status' => true, 'otp'=>$otp,'message' => 'OTP sent successfully. Please check your email.', 'data' => null], 200);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), ['phone' => 'required|exists:clients,phone', 'otp' => 'required|numeric']);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'data' => null], 402);
        }

        $storedOtp = Cache::get('otp_' . $request->phone);
        if (!$storedOtp || $storedOtp != $request->otp) {
            return response()->json(['status' => false, 'message' => 'Invalid or expired OTP.', 'data' => null], 402);
        }

        Cache::forget('otp_' . $request->phone);

        return response()->json(['status' => true, 'message' => 'OTP verified successfully. You can now reset your password.', 'data' => null], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), ['phone' => 'required|exists:clients,phone', 'password' => 'required|min:6|confirmed']);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'data' => null], 402);
        }

        $client = Client::where('phone', $request->phone)->first();
        if (!$client) return response()->json(['status' => false, 'message' => 'Client not found.', 'data' => null], 404);

        $client->password = Hash::make($request->password);
        $client->save();

        return response()->json(['status' => true, 'message' => 'Password reset successfully.', 'data' => null], 200);
    }

    // ----------------- LOGOUT -----------------
    public function logout()
    {
        $result = $this->clientRepo->logout();
        return response()->json($result);
    }

    // ----------------- GET ALL CLIENTS -----------------
    public function getAllClients()
    {
        $clients = $this->clientRepo->getAllClients();
        return response()->json(['status' => true, 'message' => 'Clients retrieved successfully', 'data' => $clients]);
    }

    // ----------------- DELETE ACCOUNT -----------------
    public function deleteAccount()
    {
        try {
            $client = $this->clientRepo->getAuthenticatedClient();
        } catch (\Exception $e) {
            $client = null;
        }

        if (!$client) {
            return response()->json(['status' => false, 'message' => 'Unauthorized', 'data' => null], 401);
        }

        $client->delete();
        $this->clientRepo->logout();

        return response()->json(['status' => true, 'message' => 'Account deleted successfully.']);
    }

    private function respondWithToken($client, $token, $message = "Success")
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => [
                'client' => [
                    'id'           => $client->id,
                    'username'     => $client->username,
                    'phone'        => $client->phone,
                    'email'        => $client->email,
                    'name'         => $client->name,
                    'photo'        => $client->photo ? asset($client->photo) : null,
                    'company_name' => $client->company_name,
                    'website'      => $client->website,
                    'address'      => $client->address,
                    'city'         => $client->city,
                    'country'      => $client->country,
                    'type'         => "client",
                ],
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }
}