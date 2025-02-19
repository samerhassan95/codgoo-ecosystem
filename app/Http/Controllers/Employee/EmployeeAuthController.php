<?php

namespace App\Http\Controllers\Employee;


use App\Http\Controllers\Controller;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Repositories\Employee\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use App\Services\ImageService;


class EmployeeAuthController extends Controller
{
    protected $EmployeeRepo;

    public function __construct(EmployeeRepositoryInterface $EmployeeRepo)
    {
        $this->EmployeeRepo = $EmployeeRepo;
    }

    public function register(Request $request)
    {
        // Validate Employee data
        $validator = Validator::make($request->all(), [
            'phone' => 'required|unique:employees,phone',
            'password' => 'required|min:6|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'intro' => 'nullable|string|max:1000', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 402);
        }
    
        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('employee_images', 'public');
        }
    
        // Handle cover photo upload
        $coverPhotoPath = null;
        if ($request->hasFile('cover_photo')) {
            $coverPhotoPath = $request->file('cover_photo')->store('employee_cover_photos', 'public');
        }
    
        // Generate OTP (Example, you can use a random generator)
        $otp = 1234;  // Generate a random OTP
    
        // Create the Employee user in the database (without OTP verification at this point)
        $employee = Employee::create([
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'name' => $request->name,
            'email' => $request->email,
            'image' => $imagePath ? asset('storage/' . $imagePath) : null,
            'cover_photo' => $coverPhotoPath ? asset('storage/' . $coverPhotoPath) : null,
            'intro' => $request->intro,
           
        ]);
    
        // Store the OTP temporarily in cache (for example, with 10 minutes expiration)
        Cache::put('otp_' . $employee->phone, $otp, now()->addMinutes(10));
    
        // Send OTP to the user (simulated, for example via email or SMS)
        // You can use a service to send an OTP here
    
        return response()->json([
            'status' => true,
            'message' => "OTP sent successfully, please verify.",
            'data' => null,
        ]);
    }
    

    public function verifyOtpAndCreateEmployee(Request $request)
    {
        // Validate OTP in the request
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
            'phone' => 'required|exists:employees,phone',
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
            $Employee = Employee::where('phone', $request->phone)->first();
            if ($Employee) {
                $Employee->delete();
            }

            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => 'Invalid OTP. User has been deleted.',
                'data' => null,
            ], 402);
        }

        $Employee = Employee::where('phone', $request->phone)->first();
        $token = auth('employee')->login($Employee);

        return response()->json([
            'status' => true,
            'message' => "OTP verified successfully.",
            'data' => [
                'id' => $Employee->id,
                'phone' => $Employee->phone,
                'email' => $Employee->email,
                'name' => $Employee->name,
                'image' => asset($Employee->image),
                'type' =>"Employee",
                'token'=>$token

            ],
        ]);
    }






    public function updateProfile(Request $request)
    {
        $Employee = auth()->user(); 

        $request->validate([
            'username' => 'sometimes|required|string|max:255|unique:Employees,username,' . $Employee->id,
            'email' => 'sometimes|required|email|max:255|unique:Employees,email,' . $Employee->id,
            'phone' => 'sometimes|required|string|max:255|unique:Employees,phone,' . $Employee->id,
            'name' => 'sometimes|required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        $imagePath = $request->hasFile('image') ? ImageService::upload($request->file('image'), 'employee_images') : null;
    

        $updated = $Employee->update([
            'username' => $request->username ?? $Employee->username,
            'name' => $request->name ?? $Employee->name,
            'email' => $request->email ?? $Employee->email,
            'phone' => $request->phone ?? $Employee->phone,
            'image' => isset($imagePath) ? asset( $imagePath) : $Employee->image,
            'company_name' => $request->company_name ?? $Employee->company_name,
            'website' => $request->website ?? $Employee->website,
            'address' => $request->address ?? $Employee->address,
            'city' => $request->city ?? $Employee->city,
            'country' => $request->country ?? $Employee->country,
        ]);

        if ($updated) {
            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully.',
                'data' => $Employee->makeHidden(['remember_token'])
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Failed to update profile.',
        ]);
    }

    
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required', 
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

        $employee = Employee::where('phone', $request->login)
                        ->first();

        if ($employee) {
            $credentials =
            [
                'password' => $request->password,
            ];

            if ($employee->phone == $request->login)
            {
                $credentials['phone'] = $request->login;
            }

            try {
                if (!$token = auth('employee')->attempt($credentials))
                {
                    return response()->json([
                        'status' => false,
                        'code' => 401,
                        'message' => __('The phone or password is incorrect'),
                        'data' => null,
                    ], 401);
                }

                $data = $employee->toArray();
                $data['token'] = $token;
                $data['type'] = 'employee'; 
                return response()->json([
                    'status' => true,
                    'code' => 200,
                    'message' => __('Employee login successful'),
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
            'message' => __('The phone does not exist'),
        ], 404);
    }
    public function logout()
    {
        $result = $this->EmployeeRepo->logout();
        return response()->json($result);
    }

    public function forgotPassword(Request $request)
    {
        $result = $this->EmployeeRepo->forgotPassword($request->phone);
        return response()->json($result);
    }

    public function getProfile(Request $request)
    {
        $Employee = auth('employee')->user();

        if (!$Employee) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Employee not found.',
                'data' => null
            ], 401);
        }

        return response()->json([
            'status' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'Employee' => $Employee,
                'token' => $request->bearerToken(),
                'type' => 'Employee',
            ],
        ]);
    }


    public function forgotPasswordRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|exists:Employees,phone',
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
            'phone' => 'required|exists:Employees,phone',
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
            'phone' => 'required|exists:Employees,phone',
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

        // Find Employee by phone
        $Employee = Employee::where('phone', $phone)->first();

        if (!$Employee) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found.',
                'data' => null
            ], 404);
        }

        // Update password
        $Employee->password = Hash::make($newPassword);
        $Employee->save();

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

        // Get the authenticated Employee
        $Employee = auth('Employee')->user();

        if (!$Employee) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Employee not found.',
                'data' => null
            ], 401);
        }

        // Update the password
        $Employee->password = Hash::make($request->new_password);
        $Employee->save();

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
            'new_phone' => 'required|unique:Employees,phone', // Ensure it's unique in the Employees table
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 402);
        }

        // Get the authenticated Employee
        $Employee = auth('Employee')->user();

        if (!$Employee) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Employee not found.',
                'data' => null
            ], 401);
        }

        // Generate OTP
        $otp = 1234; // Generate a 4-digit OTP

        // Store the OTP and new phone in cache (with a unique key, e.g., phone + Employee ID)
        Cache::put('otp_change_phone_' . $Employee->id, [
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

        // Get the authenticated Employee
        $Employee = auth('Employee')->user();

        if (!$Employee) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Employee not found.',
                'data' => null
            ], 401);
        }

        // Retrieve the OTP and new phone from cache
        $cachedData = Cache::get('otp_change_phone_' . $Employee->id);

        if (!$cachedData || $cachedData['otp'] != $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => null,
            ], 402);
        }

        // Update the phone number
        $Employee->phone = $cachedData['new_phone'];
        $Employee->save();

        // Remove the cached OTP after successful verification
        Cache::forget('otp_change_phone_' . $Employee->id);

        return response()->json([
            'status' => true,
            'message' => 'Phone number updated successfully.',
            'data' => [
                'phone' => $Employee->phone,
            ],
        ], 200);
    }

    
}
