<?php

namespace App\Http\Controllers\Client;


use App\Http\Controllers\Controller;

use App\Models\Client;
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
        // Validate client data
        $validator = Validator::make($request->all(), [
            'phone' => 'required|unique:clients,phone',
            'password' => 'required|min:6|max:255',
            'username' => 'required|unique:clients|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 402);
        }

        // Handle photo upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('client_photos', 'public');
        }

        // Generate OTP
        $otp = 1234;  // Generate a random OTP

        // Create the client user in the database (without OTP verification at this point)
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
        ]);

        // Store the OTP temporarily in cache (for example, with 10 minutes expiration)
        Cache::put('otp_' . $client->phone, $otp, now()->addMinutes(10));

        // Send OTP to the user (simulated, for example via email or SMS)
        // You can use a service to send an OTP here

        return response()->json([
            'status' => true,
            'message' => "OTP sent successfully, please verify.",
            'data' => null,
        ]);
    }

    public function verifyOtpAndCreateClient(Request $request)
    {
        // Validate OTP in the request
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
            'phone' => 'required|exists:clients,phone',  // Ensure phone exists
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 402);
        }

        // Retrieve the OTP from cache
        $storedOtp = Cache::get('otp_' . $request->phone);

        if (!$storedOtp) {
            // OTP has expired or is invalid
            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => 'OTP has expired or is invalid.',
                'data' => null,
            ], 402);
        }

        // Check if the OTP is correct
        if ($storedOtp != $request->otp) {
            // OTP is incorrect, delete the client from the database
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

        // OTP is valid, return the created user data
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
        $client = auth()->user();  // Get the currently authenticated user

        // Validation rules (with unique checks)
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

        // If there's a photo, handle the upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = (new ImageService())->upload($request->file('photo'), 'clients');
        }

        // Update profile fields
        $updated = $client->update([
            'username' => $request->username ?? $client->username,
            'name' => $request->name ?? $client->name,
            'email' => $request->email ?? $client->email,
            'phone' => $request->phone ?? $client->phone,
            'photo' => isset($photoPath) ? asset( $photoPath) : $client->photo,
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
                'data' => $client->makeHidden(['remember_token'])
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Failed to update profile.',
        ]);
    }

    



    //  public function login(Request $request)
    //  {
    //      $validator = Validator::make($request->all(), [
    //          'login' => 'required', // 'login' can be either phone or username
    //          'password' => 'required',
    //      ]);

    //      if ($validator->fails()) {
    //          return response()->json([
    //              "status" => false,
    //              'code' => 402,
    //              'message' => $validator->errors()->first(),
    //              'data' => null,
    //          ], 402);
    //      }

    //      // Check if the login is a phone number or username
    //      $client = Client::where('phone', $request->login) // Check if it's a phone number
    //                     ->orWhere('username', $request->login) // Or if it's a username
    //                     ->first();

    //      if ($client) {
    //          // Set credentials for phone or username
    //          $credentials = [
    //              'password' => $request->password,
    //          ];

    //          if ($client->phone == $request->login) {
    //              // If login is phone, pass phone with password to JWTAuth attempt
    //              $credentials['phone'] = $request->login;
    //          } else {
    //              // If login is username, pass username with password to JWTAuth attempt
    //              $credentials['username'] = $request->login;
    //          }

    //          try {
    //              // Authenticate the client with the client guard
    //              if (!$token = auth('client')->attempt($credentials)) {
    //                  return response()->json([
    //                      'status' => false,
    //                      'code' => 401,
    //                      'message' => __('The phone/username or password is incorrect'),
    //                      'data' => null,
    //                  ], 401);
    //              }
    //          } catch (JWTException $e) {
    //              return response()->json([
    //                  'status' => false,
    //                  'code' => 500,
    //                  'message' => __('Server error, please try again later'),
    //                  'data' => null,
    //              ], 500);
    //          }

    //          // If successful, return the client's data and token
    //          $data = $client->toArray();  // Convert client model to array
    //          $data['token'] = $token; // Assign the generated token to the data array
    //          $data['type'] = 'client';

    //          return response()->json([
    //              'status' => true,
    //              'code' => 200,
    //              'message' => __('Login successful'),
    //              'data' => $data,
    //          ], 200);
    //      }

    //      return response()->json([
    //          'status' => false,
    //          'message' => __('The phone/username does not exist'),
    //      ], 404);
    //  }

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
            'data' => [
                'client' => $client,
                'token' => $request->bearerToken(),
                'type' => 'client',
            ],
        ]);
    }


    public function forgotPasswordRequest(Request $request)
    {
        // Validate phone number
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

        // Generate OTP (4 digits)
        $otp = 1234;

        // Store OTP in cache for 5 minutes
        Cache::put('otp', $otp,  now()->addMinutes(10));  // Store OTP in cache for 5 minutes

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
        // Validate the OTP and phone number
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

        // Retrieve phone and OTP from request
        $phone = $request->phone;
        $otp = $request->otp;

        // Check if OTP exists in cache and matches
        $storedOtp = Cache::get('otp');
        // dd($storedOtp);
        if (!$storedOtp || $storedOtp != $otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => null
            ], 402);
        }

        // Optionally, delete the OTP from cache after it's verified
        Cache::forget('otp_' . $phone);

        return response()->json([
            'status' => true,
            'message' => 'OTP verified successfully. You can now reset your password.',
            'data' => null
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        // Validate password and phone number
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

        // Retrieve phone and new password from request
        $phone = $request->phone;
        $newPassword = $request->password;

        // Retrieve the stored OTP from cache (ensure it's verified before)
        $storedOtp = Cache::get('otp');

        if (!$storedOtp) {
            return response()->json([
                'status' => false,
                'message' => 'OTP has either expired or was not verified.',
                'data' => null
            ], 402);
        }

        // Find client by phone
        $client = Client::where('phone', $phone)->first();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Client not found.',
                'data' => null
            ], 404);
        }

        // Update password
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
            'new_password' => 'required|min:6|confirmed', // Must match new_password_confirmation
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 402);
        }

        // Get the authenticated client
        $client = auth('client')->user();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Client not found.',
                'data' => null
            ], 401);
        }

        // Update the password
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
        // Validate the new phone number
        $validator = Validator::make($request->all(), [
            'new_phone' => 'required|unique:clients,phone', // Ensure it's unique in the clients table
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 402);
        }

        // Get the authenticated client
        $client = auth('client')->user();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Client not found.',
                'data' => null
            ], 401);
        }

        // Generate OTP
        $otp = 1234; // Generate a 4-digit OTP

        // Store the OTP and new phone in cache (with a unique key, e.g., phone + client ID)
        Cache::put('otp_change_phone_' . $client->id, [
            'otp' => $otp,
            'new_phone' => $request->new_phone,
        ], now()->addMinutes(10)); // OTP valid for 10 minutes

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
        // Validate the OTP
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

        // Get the authenticated client
        $client = auth('client')->user();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Client not found.',
                'data' => null
            ], 401);
        }

        // Retrieve the OTP and new phone from cache
        $cachedData = Cache::get('otp_change_phone_' . $client->id);

        if (!$cachedData || $cachedData['otp'] != $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => null,
            ], 402);
        }

        // Update the phone number
        $client->phone = $cachedData['new_phone'];
        $client->save();

        // Remove the cached OTP after successful verification
        Cache::forget('otp_change_phone_' . $client->id);

        return response()->json([
            'status' => true,
            'message' => 'Phone number updated successfully.',
            'data' => [
                'phone' => $client->phone,
            ],
        ], 200);
    }

    
}
