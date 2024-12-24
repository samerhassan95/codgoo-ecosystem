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

// class AdminAuthController extends Controller
// {
//     // // Admin Authentication
//     // public function authenticate(Request $request)
//     // {
//     //     $validator = Validator::make($request->all(), [
//     //         'login' => 'required', // 'login' can be either phone or username
//     //         'password' => 'required',
//     //     ]);

//     //     if ($validator->fails()) {
//     //         return response()->json([
//     //             "status" => false,
//     //             'code' => 402,
//     //             'message' => $validator->errors()->first(),
//     //             'data' => null,
//     //         ], 402);
//     //     }

//     //     $credentials = [
//     //         'password' => $request->password,
//     //     ];

//     //     // Check if the login is a phone number or username
//     //     $admin = Admin::where('phone', $request->login) // Check if it's a phone number
//     //                   ->orWhere('username', $request->login) // Or if it's a username
//     //                   ->first();

//     //     if ($admin) {
//     //         // Set credentials for phone or username
//     //         if ($admin->phone == $request->login) {
//     //             $credentials['phone'] = $request->login;
//     //         } else {
//     //             $credentials['username'] = $request->login;
//     //         }

//     //         try {
//     //             // Try to authenticate the user
//     //             if (!$token = auth('admin')->attempt($credentials)) {
//     //                 return response()->json([
//     //                     'status' => false,
//     //                     'code' => 401,
//     //                     'message' => __('The phone/username or password is incorrect'),
//     //                     'data' => null,
//     //                 ], 401);
//     //             }
//     //         } catch (JWTException $e) {
//     //             return response()->json([
//     //                 'status' => false,
//     //                 'code' => 500,
//     //                 'message' => __('Server error, please try again later'),
//     //                 'data' => null,
//     //             ], 500);
//     //         }

//     //         // If successful, return the admin's data and token
//     //         $data = $admin;
//     //         $data['token'] = $token;
//     //         $data['type'] = 'admin';

//     //         return response()->json([
//     //             'status' => true,
//     //             'code' => 200,
//     //             'message' => __('Login successful'),
//     //             'data' => $data,
//     //         ], 200);
//     //     }

//     //     return response()->json([
//     //         'status' => false,
//     //         'message' => __('The phone/username does not exist'),
//     //     ], 404);
//     // }



//     // // Admin Registration
//     // public function register(Request $request)
//     // {
//     //     $validator = Validator::make($request->all(), [
//     //         'phone' => 'required|unique:admins,email',
//     //         'password' => 'required|min:6|max:255',
//     //         'name' => 'required|max:255',
//     //     ]);

//     //     if ($validator->fails()) {
//     //         return response()->json([
//     //             "status" => false,
//     //             'code' => 402,
//     //             'message' => $validator->errors()->first(),
//     //             'data' => null,
//     //         ], 402);
//     //     }

//     //     Admin::create([
//     //         "name" => $request->name,
//     //         "email" => $request->email,
//     //         "password" => Hash::make($request->password),
//     //     ]);

//     //     return response()->json([
//     //         'status' => true,
//     //         'code' => 200,
//     //         'message' => "Admin account created successfully",
//     //     ], 200);
//     // }

//     // // Admin Logout
//     // public function logout()
//     // {
//     //     Auth::guard('admin')->logout();

//     //     return response()->json([
//     //         'status' => true,
//     //         'message' => __('Logout successful'),
//     //     ], 200);
//     // }

//     // // Update Admin Profile
//     // public function changeProfile(Request $request)
//     // {
//     //     $auth = Auth::guard('admin')->user();
//     //     $admin = Admin::find($auth->id);
//     //     $admin->update($request->except('password'));

//     //     return response()->json([
//     //         'status' => true,
//     //         'code' => 200,
//     //         'message' => "Profile updated successfully",
//     //         'data' => $admin,
//     //     ], 200);
//     // }

//     // // Change Admin Password
//     // public function changePassword(Request $request)
//     // {
//     //     $validator = Validator::make($request->all(), [
//     //         'password' => 'required|min:6|max:255',
//     //     ]);

//     //     if ($validator->fails()) {
//     //         return response()->json([
//     //             "status" => false,
//     //             'code' => 402,
//     //             'message' => $validator->errors()->first(),
//     //             'data' => null,
//     //         ], 402);
//     //     }

//     //     $auth = Auth::guard('admin')->user();
//     //     $admin = Admin::find($auth->id);
//     //     $admin->update(['password' => Hash::make($request->password)]);

//     //     return response()->json([
//     //         'status' => true,
//     //         'code' => 200,
//     //         'message' => "Password updated successfully",
//     //     ], 200);
//     // }

//     // // Forgot Admin Password
//     // public function forgetPassword(Request $request)
//     // {
//     //     $validator = Validator::make($request->all(), [
//     //         'email' => 'required|email',
//     //         'password' => 'required|min:6|max:255',
//     //     ]);

//     //     if ($validator->fails()) {
//     //         return response()->json([
//     //             "status" => false,
//     //             'code' => 402,
//     //             'message' => $validator->errors()->first(),
//     //             'data' => null,
//     //         ], 402);
//     //     }

//     //     $admin = Admin::where('email', $request->email)->first();

//     //     if (!$admin) {
//     //         return response()->json([
//     //             "status" => false,
//     //             'code' => 404,
//     //             'message' => "Email not found",
//     //             'data' => null,
//     //         ], 404);
//     //     }

//     //     $admin->update(['password' => Hash::make($request->password)]);

//     //     return response()->json([
//     //         'status' => true,
//     //         'code' => 200,
//     //         'message' => "Password updated successfully",
//     //     ], 200);
//     // }

//     // // Get Admin Profile
//     // public function getProfile()
//     // {
//     //     $auth = Auth::guard('admin')->user();
//     //     $admin = Admin::find($auth->id);

//     //     return response()->json([
//     //         'status' => true,
//     //         'code' => 200,
//     //         'message' => "Admin Profile",
//     //         'data' => $admin,
//     //     ], 200);
//     // }



//       // Admin Register
//       public function register(Request $request)
//       {
//           $validator = Validator::make($request->all(), [
//               'username' => 'required|unique:admins',
//               'phone' => 'required|unique:admins',
//               'password' => 'required|min:6',
//           ]);

//           if ($validator->fails()) {
//               return response()->json(['errors' => $validator->errors()], 422);
//           }

//           $admin = Admin::create([
//               'username' => $request->username,
//               'phone' => $request->phone,
//               'password' => Hash::make($request->password),
//           ]);

//           $token = JWTAuth::fromUser($admin);

//           return response()->json([
//               'admin' => $admin,
//               'token' => $token,
//           ], 201);
//       }

//       // Admin Login
//       public function login(Request $request)
//       {
//           $credentials = $request->only('username', 'password');

//           if ($token = JWTAuth::attempt($credentials)) {
//               return response()->json(['token' => $token]);
//           }

//           return response()->json(['error' => 'Unauthorized'], 401);
//       }

//       // Admin Logout
//       public function logout()
//       {
//           JWTAuth::invalidate(JWTAuth::getToken());

//           return response()->json(['message' => 'Successfully logged out']);
//       }

//       // Admin Forgot Password (you can modify this to send a reset email)
//       public function forgotPassword(Request $request)
//       {
//           $request->validate([
//               'phone' => 'required',
//           ]);

//           $admin = Admin::where('phone', $request->phone)->first();

//           if (!$admin) {
//               return response()->json(['error' => 'Admin not found'], 404);
//           }

//           // Generate and send reset token logic here (or use Laravel's built-in reset functionality)
//           return response()->json(['message' => 'Password reset link sent.'], 200);
//       }

// }
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
            'phone' => 'required|unique:admins,phone',
            'password' => 'required|min:6|max:255',
            'username' => 'required|unique|max:255',
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
            'login' => 'required', // 'login' can be either phone or username
            'password' => 'required',
                ]);

        if ($validator->fails())
        {
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
            $credentials =
            [
                'password' => $request->password,
            ];

            if ($client->phone == $request->login)
            {
                $credentials['phone'] = $request->login;
            } else
            {
                $credentials['username'] = $request->login;
            }

            try {
                if (!$token = auth('client')->attempt($credentials))
                {
                    return response()->json([
                        'status' => false,
                        'code' => 401,
                        'message' => __('The phone/username or password is incorrect'),
                        'data' => null,
                    ], 401);
                }

                $data = $client->toArray();
                $data['token'] = $token;
                $data['type'] = 'client'; // Specify the type as 'client'
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

        // Check if the login is a phone number or username for Admin
        $admin = Admin::where('phone', $request->login)
                      ->orWhere('username', $request->login)
                      ->first();

        if ($admin) {
            // Set credentials for phone or username
            $credentials = [
                'password' => $request->password,
            ];

            if ($admin->phone == $request->login) {
                $credentials['phone'] = $request->login;
            } else {
                $credentials['username'] = $request->login;
            }

            try {
                // Authenticate the admin with the 'admin' guard
                if (!$token = auth('admin')->attempt($credentials)) {
                    return response()->json([
                        'status' => false,
                        'code' => 401,
                        'message' => __('The phone/username or password is incorrect'),
                        'data' => null,
                    ], 401);
                }

                // Return admin data and token
                $data = $admin->toArray();
                $data['token'] = $token;
                $data['type'] = 'admin'; // Specify the type as 'admin'
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

        // If no client or admin found, return an error
        return response()->json([
            'status' => false,
            'message' => __('The phone/username does not exist'),
        ], 404);
    }



    // Admin Logout
    public function logout()
    {
        Auth::guard('admin')->logout();

        return response()->json([
            'status' => true,
            'message' => __('Logout successful'),
        ], 200);
    }



    // Update Admin Profile
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
