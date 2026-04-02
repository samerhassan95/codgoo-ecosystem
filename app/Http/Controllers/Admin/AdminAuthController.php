<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Repositories\Admin\AdminRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    protected $adminRepo;

    public function __construct(AdminRepositoryInterface $adminRepo)
    {
        $this->adminRepo = $adminRepo;
    }


    // Admin Registration
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => [
                'required',
                'unique:admins,phone',
                function ($attribute, $value, $fail) {
                    if (Client::where('phone', $value)->exists()) {
                        $fail("This phone number is already registered as a client.");
                    }
                },
            ],
            'username' => [
                'required',
                'unique:admins,username',
                function ($attribute, $value, $fail) {
                    if (Client::where('username', $value)->exists()) {
                        $fail("This username is already registered as a client.");
                    }
                },
            ],
            'password' => 'required|min:6|max:255',
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

        Admin::create([
            "username" => $request->username,
            "phone" => $request->phone,
            "password" => Hash::make($request->password),
            "device_token" => $request->device_token, 
        ]);

        return response()->json([
            'status' => true,
            'code' => 200,
             "device_token" => $request->device_token,
            'message' => "Admin account created successfully",
        ], 200);
    }
    
    // public function login(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required',
    //         'password' => 'required',
    //         'device_token' => 'nullable|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             "status" => false,
    //             'code' => 402,
    //             'message' => $validator->errors()->first(),
    //             'data' => null,
    //         ], 402);
    //     }

    //     $client = Client::where('email', $request->email)
    //                     // ->orWhere('username', $request->login)
    //                     ->first();

    //     if ($client) {
    //         $credentials = ['password' => $request->password];

    //         if ($client->email == $request->email) {
    //             $credentials['email'] = $request->email;}
    //         // } else {
    //         //     $credentials['username'] = $request->login;
    //         // }

    //         try {
    //             if (!$token = auth('client')->attempt($credentials)) {
    //                 return response()->json([
    //                     'status' => false,
    //                     'code' => 401,
    //                     'message' => __('The phone/username or password is incorrect'),
    //                     'data' => null,
    //                 ], 401);
    //             }

    //             $client->update(['device_token' => $request->device_token]);

    //             $data = $client->toArray();
    //             $data['token'] = $token;
    //             $data['photo'] = $client->photo ? asset($client->photo) : null;
    //             $data['type'] = 'client';

    //             return response()->json([
    //                 'status' => true,
    //                 'code' => 200,
    //                 'message' => __('Client login successful'),
    //                 'data' => $data,
    //             ], 200);

    //         } catch (JWTException $e) {
    //             return response()->json([
    //                 'status' => false,
    //                 'code' => 500,
    //                 'message' => __('Server error, please try again later'),
    //                 'data' => null,
    //             ], 500);
    //         }
    //     }

    //     $admin = Admin::where('email', $request->email)
    //                 // ->orWhere('username', $request->login)
    //                 ->first();

    //     if ($admin) {
    //         $credentials = ['password' => $request->password];

    //         if ($admin->email == $request->email) {
    //             $credentials['email'] = $request->email;}
    //         // } else {
    //         //     $credentials['username'] = $request->login;
    //         // }

    //         try {
    //             if (!$token = auth('admin')->attempt($credentials)) {
    //                 return response()->json([
    //                     'status' => false,
    //                     'code' => 401,
    //                     'message' => __('The phone/username or password is incorrect'),
    //                     'data' => null,
    //                 ], 401);
    //             }

    //             $admin->update(['device_token' => $request->device_token]);

    //             $data = $admin->toArray();
    //             $data['token'] = $token;
    //             $data['type'] = 'admin';

    //             return response()->json([
    //                 'status' => true,
    //                 'code' => 200,
    //                 'message' => __('Admin login successful'),
    //                 'data' => $data,
    //             ], 200);

    //         } catch (JWTException $e) {
    //             return response()->json([
    //                 'status' => false,
    //                 'code' => 500,
    //                 'message' => __('Server error, please try again later'),
    //                 'data' => null,
    //             ], 500);
    //         }
    //     }

    //     return response()->json([
    //         'status' => false,
    //         'message' => __('The email/password does not exist'),
    //     ], 404);
    // }

public function login(Request $request)
{
    $validator = Validator::make(
        $request->all(),
        [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'device_token' => 'nullable|string',
            'device' => 'nullable|string',
        ],
        [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ]
    );
    
    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'code' => 422,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

    $invalidCredentialsResponse = response()->json([
        'status' => false,
        'code' => 401,
        'message' => 'Invalid email or password.',
        'data' => null,
    ], 401);
    
    // ================= Client =================
    $client = Client::where('email', $request->email)->first();
    if ($client) {
        try {
            // Set TTL: 10 years for mobile, default for web
            if ($request->device === 'mobile') {
                auth('client')->factory()->setTTL(525600 * 10); // 10 years in minutes
            }
            
            if (!$token = auth('client')->attempt([
                'email' => $request->email,
                'password' => $request->password
            ])) {
                return $invalidCredentialsResponse;
            }
            
            $client->update(['device_token' => $request->device_token]);
            
            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Client login successful.',
                'data' => [
                    ...$client->toArray(),
                    'token' => $token,
                    'photo' => $client->photo ? asset($client->photo) : null,
                    'type' => 'client',
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'Server error, please try again later.',
                'data' => null,
            ], 500);
        }
    }
    
    // ================= Admin =================
    $admin = Admin::where('email', $request->email)->first();
    if ($admin) {
        try {
            // Set TTL: 10 years for mobile, default for web
            if ($request->device === 'mobile') {
                auth('admin')->factory()->setTTL(525600 * 10); // 10 years in minutes
            }
            
            if (!$token = auth('admin')->attempt([
                'email' => $request->email,
                'password' => $request->password
            ])) {
                return $invalidCredentialsResponse;
            }
            
            $admin->update(['device_token' => $request->device_token]);
            
            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Admin login successful.',
                'data' => [
                    ...$admin->toArray(),
                    'token' => $token,
                    'type' => 'admin',
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'Server error, please try again later.',
                'data' => null,
            ], 500);
        }
    }
    
    return $invalidCredentialsResponse;
}



    public function logout()
    {
        Auth::guard('admin')->logout();

        return response()->json([
            'status' => true,
            'message' => __('Logout successful'),
        ], 200);
    }



    public function changeProfile(Request $request)
    {
        $auth = Auth::guard('admin')->user();
        $admin = Admin::find($auth->id);
        $admin->update($request->except('password'));

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => "Profile updated successfully",
            'data' => $admin,
        ], 200);
    }
    // Change Admin Password
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:6|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 402);
        }

        $auth = Auth::guard('admin')->user();
        $admin = Admin::find($auth->id);
        $admin->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => "Password updated successfully",
        ], 200);
    }

    // Forgot Admin Password
    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 402);
        }

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            return response()->json([
                "status" => false,
                'code' => 404,
                'message' => "Email not found",
                'data' => null,
            ], 404);
        }

        $admin->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => "Password updated successfully",
        ], 200);
    }

    // Get Admin Profile
    public function getProfile()
    {
        $auth = Auth::guard('admin')->user();
        $admin = Admin::find($auth->id);

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => "Admin Profile",
            'data' => $admin,
        ], 200);
    }

}
