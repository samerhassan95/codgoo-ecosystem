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
            'message' => "Admin account created successfully",
        ], 200);
    }
    
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required',
            'password' => 'required',
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

        $client = Client::where('phone', $request->login)
                        ->orWhere('username', $request->login)
                        ->first();

        if ($client) {
            $credentials = ['password' => $request->password];

            if ($client->phone == $request->login) {
                $credentials['phone'] = $request->login;
            } else {
                $credentials['username'] = $request->login;
            }

            try {
                if (!$token = auth('client')->attempt($credentials)) {
                    return response()->json([
                        'status' => false,
                        'code' => 401,
                        'message' => __('The phone/username or password is incorrect'),
                        'data' => null,
                    ], 401);
                }

                $client->update(['device_token' => $request->device_token]);

                $data = $client->toArray();
                $data['token'] = $token;
                $data['type'] = 'client';

                return response()->json([
                    'status' => true,
                    'code' => 200,
                    'message' => __('Client login successful'),
                    'data' => $data,
                ], 200);

            } catch (JWTException $e) {
                return response()->json([
                    'status' => false,
                    'code' => 500,
                    'message' => __('Server error, please try again later'),
                    'data' => null,
                ], 500);
            }
        }

        $admin = Admin::where('phone', $request->login)
                    ->orWhere('username', $request->login)
                    ->first();

        if ($admin) {
            $credentials = ['password' => $request->password];

            if ($admin->phone == $request->login) {
                $credentials['phone'] = $request->login;
            } else {
                $credentials['username'] = $request->login;
            }

            try {
                if (!$token = auth('admin')->attempt($credentials)) {
                    return response()->json([
                        'status' => false,
                        'code' => 401,
                        'message' => __('The phone/username or password is incorrect'),
                        'data' => null,
                    ], 401);
                }

                $admin->update(['device_token' => $request->device_token]);

                $data = $admin->toArray();
                $data['token'] = $token;
                $data['type'] = 'admin';

                return response()->json([
                    'status' => true,
                    'code' => 200,
                    'message' => __('Admin login successful'),
                    'data' => $data,
                ], 200);

            } catch (JWTException $e) {
                return response()->json([
                    'status' => false,
                    'code' => 500,
                    'message' => __('Server error, please try again later'),
                    'data' => null,
                ], 500);
            }
        }

        return response()->json([
            'status' => false,
            'message' => __('The phone/username does not exist'),
        ], 404);
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
