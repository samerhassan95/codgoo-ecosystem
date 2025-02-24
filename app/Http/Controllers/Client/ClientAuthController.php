<?php

namespace App\Http\Controllers\Client;


use App\Http\Controllers\Controller;

use App\Models\Client;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Repositories\Client\ClientRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use App\Services\ImageService;


class ClientAuthController extends Controller
{
    protected $clientRepo;

    public function __construct(ClientRepositoryInterface $clientRepo)
    {
        $this->clientRepo = $clientRepo;
    }

    
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => [
                'required',
                'unique:clients,phone',
                function ($attribute, $value, $fail) {
                    if (Admin::where('phone', $value)->exists()) {
                        $fail("This phone number is already registered as an admin.");
                    }
                },
            ],
            'username' => [
                'required',
                'unique:clients,username',
                function ($attribute, $value, $fail) {
                    if (Admin::where('username', $value)->exists()) {
                        $fail("This username is already registered as an admin.");
                    }
                },
            ],
            'password' => 'required|min:6|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'unique:clients,email',
            ],
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
                'code' => 402,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 402);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = ImageService::upload($request->file('photo'), 'client_photos');
        }

        $otp = 1234; 
        $client = Client::create([
            'username' => $request->username,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'name' => $request->name,
            'email' => $request->email,
            'photo' => $photoPath,
            'company_name' => $request->company_name,
            'website' => $request->website,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'device_token' => $request->device_token, 
        ]);

        Cache::put('otp_' . $client->phone, $otp, now()->addMinutes(10));

        return response()->json([
            'status' => true,
            'message' => "OTP sent successfully, please verify.",
            'data' => null,
        ]);
    }


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

        if (!$storedOtp) {
            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => 'OTP has expired or is invalid.',
                'data' => null,
            ], 402);
        }

        if ($storedOtp != $request->otp) {
            $client = Client::where('phone', $request->phone)->first();
            if ($client) {
                $client->delete();
            }

            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => 'Invalid OTP. User has been deleted.',
                'data' => null,
            ], 402);
        }

        $client = Client::where('phone', $request->phone)->first();
        $token = auth('client')->login($client);

        return response()->json([
            'status' => true,
            'message' => "OTP verified successfully.",
            'data' => [
                'id' => $client->id,
                'username' => $client->username,
                'phone' => $client->phone,
                'email' => $client->email,
                'name' => $client->name,
                'photo' => asset($client->photo),
                'company_name' => $client->company_name,
                'website' => $client->website,
                'address' => $client->address,
                'city' => $client->city,
                'country' => $client->country,
                'type' =>"Client",
                'token'=>$token

            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $client = auth()->user();
    
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
    
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = (new ImageService())->upload($request->file('photo'), 'client_photos');
        }
    
        $updated = $client->update([
            'username' => $request->username ?? $client->username,
            'name' => $request->name ?? $client->name,
            'email' => $request->email ?? $client->email,
            'phone' => $request->phone ?? $client->phone,
            'photo' => isset($photoPath) ? asset($photoPath) : $client->photo,
            'company_name' => $request->company_name ?? $client->company_name,
            'website' => $request->website ?? $client->website,
            'address' => $request->address ?? $client->address,
            'city' => $request->city ?? $client->city,
            'country' => $request->country ?? $client->country,
        ]);
    
        if ($updated) {
            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully.',
                'data' => array_merge(
                    $client->makeHidden(['remember_token', 'password'])->toArray(), 
                    [
                        'token' => $request->bearerToken(), 
                        'type' => 'client'
                    ]
                ),
            ], 200);
        }
    
        return response()->json([
            'status' => false,
            'message' => 'Failed to update profile.',
        ]);
    }
    


    public function logout()
    {
        $result = $this->clientRepo->logout();
        return response()->json($result);
    }

    public function forgotPassword(Request $request)
    {
        $result = $this->clientRepo->forgotPassword($request->phone);
        return response()->json($result);
    }

    public function getProfile(Request $request)
    {
        $client = auth('client')->user();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Client not found.',
                'data' => null
            ], 401);
        }

        return response()->json([
            'status' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => array_merge(
                $client->makeHidden(['remember_token', 'password'])->toArray(), 
                [
                    'token' => $request->bearerToken(), 
                    'type' => 'client'
                ]
            ),
        ]);
    }

    public function forgotPasswordRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|exists:clients,phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Phone number does not exist.',
                'data' => null
            ], 404);
        }

        $phone = $request->phone;

        $otp = 1234;

        Cache::put('otp', $otp,  now()->addMinutes(10));  

        // Simulate sending OTP (you can integrate an SMS service here)
        // For now, we'll just log the OTP (In production, send via SMS)
        // Log::info("OTP for phone {$phone}: {$otp}");

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully. Please check your phone.',
            'data' => null
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|exists:clients,phone',
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 402);
        }

        $phone = $request->phone;
        $otp = $request->otp;

        $storedOtp = Cache::get('otp');
        if (!$storedOtp || $storedOtp != $otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => null
            ], 402);
        }

        Cache::forget('otp_' . $phone);

        return response()->json([
            'status' => true,
            'message' => 'OTP verified successfully. You can now reset your password.',
            'data' => null
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|exists:clients,phone',
            'password' => 'required|min:6|confirmed',  
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 402);
        }

        $phone = $request->phone;
        $newPassword = $request->password;

        $storedOtp = Cache::get('otp');

        if (!$storedOtp) {
            return response()->json([
                'status' => false,
                'message' => 'OTP has either expired or was not verified.',
                'data' => null
            ], 402);
        }

        $client = Client::where('phone', $phone)->first();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Client not found.',
                'data' => null
            ], 404);
        }

        $client->password = Hash::make($newPassword);
        $client->save();

        return response()->json([
            'status' => true,
            'message' => 'Password reset successfully.',
            'data' => null
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 402);
        }

        $client = auth('client')->user();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Client not found.',
                'data' => null
            ], 401);
        }

        $client->password = Hash::make($request->new_password);
        $client->save();

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully.',
            'data' => null
        ], 200);
    }


    public function changePhoneRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_phone' => 'required|unique:clients,phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 402);
        }

        $client = auth('client')->user();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Client not found.',
                'data' => null
            ], 401);
        }

        $otp = 1234;

        Cache::put('otp_change_phone_' . $client->id, [
            'otp' => $otp,
            'new_phone' => $request->new_phone,
        ], now()->addMinutes(10));

        // Simulate sending the OTP (you can integrate an SMS service here)
        // Log::info("OTP for phone {$request->new_phone}: {$otp}"); // For debugging only
        // Send SMS or notification here

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to the new phone number.',
            'data' => null,
        ], 200);
    }

    public function verifyChangePhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 402);
        }

        $client = auth('client')->user();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Client not found.',
                'data' => null
            ], 401);
        }

        $cachedData = Cache::get('otp_change_phone_' . $client->id);

        if (!$cachedData || $cachedData['otp'] != $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => null,
            ], 402);
        }

        $client->phone = $cachedData['new_phone'];
        $client->save();

        Cache::forget('otp_change_phone_' . $client->id);

        return response()->json([
            'status' => true,
            'message' => 'Phone number updated successfully.',
            'data' => [
                'phone' => $client->phone,
            ],
        ], 200);
    }

    public function getAllClients()
    {
        $clients = $this->clientRepo->getAllClients();

        return response()->json([
            'status' => true,
            'message' => 'Clients retrieved successfully',
            'data' => $clients
        ]);
    }

    public function deleteAccount()
    {
        $client = auth()->user(); 

        if (!$client) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $client->delete();

        auth()->logout();

        return response()->json(['message' => 'Account deleted successfully.'], 200);
    }


}
