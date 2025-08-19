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
        $validator = Validator::make($request->all(), [
            'phone' => 'required|unique:employees,phone',
            'password' => 'required|min:6|max:255',
            // 'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // 'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:employees,email',
            // 'intro' => 'nullable|string|max:1000',
            'role' => 'required|in:ui_ux,front_end,back_end,tester,mobile',
            'device_token' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => $validator->errors()->first(),
                'data' => null,
            ], 402);
        }

        // $imagePath = $request->hasFile('image') ? ImageService::upload($request->file('image'), 'employee_images') : null;
        // $coverPhotoPath = $request->hasFile('cover_photo') ? ImageService::upload($request->file('cover_photo'), 'employee_cover_photos') : null;

        $otp = 1234;

        $cachedData = [
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'name' => $request->name,
            'email' => $request->email,
            // 'image' => $imagePath,
            // 'cover_photo' => $coverPhotoPath,
            'intro' => $request->intro,
            'role' => $request->role,
            'device_token' => $request->device_token, 
        ];

        Cache::put('employee_register_' . $request->phone, $cachedData, now()->addMinutes(10));
        Cache::put('otp_' . $request->phone, $otp, now()->addMinutes(10));

        return response()->json([
            'status' => true,
            'message' => "OTP sent successfully, please verify.",
            'data' => null,
        ]);
    }

    public function verifyOtpAndCreateEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
            'phone' => 'required',
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
        $cachedEmployee = Cache::get('employee_register_' . $request->phone);

        if (!$storedOtp || !$cachedEmployee) {
            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => 'OTP has expired or registration data not found.',
                'data' => null,
            ], 402);
        }

        if ($storedOtp != $request->otp) {
            Cache::forget('otp_' . $request->phone);
            Cache::forget('employee_register_' . $request->phone);

            return response()->json([
                "status" => false,
                'code' => 402,
                'message' => 'Invalid OTP.',
                'data' => null,
            ], 402);
        }

        $employee = Employee::create([
            ...$cachedEmployee
        ]);

        Cache::forget('otp_' . $request->phone);
        Cache::forget('employee_register_' . $request->phone);

        $token = auth('employee')->login($employee);

        return response()->json([
            'status' => true,
            'message' => "OTP verified successfully.",
            'data' => [
                'id' => $employee->id,
                'phone' => $employee->phone,
                'email' => $employee->email,
                'name' => $employee->name,
                'image' => asset($employee->image),
                'role' => $employee->role,
                'token' => $token,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $employee = auth()->user(); 
    
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:255|unique:employees,phone,' . $employee->id,
            'email' => 'sometimes|required|email|max:255|unique:employees,email,' . $employee->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'intro' => 'nullable|string|max:1000',
            'role' => 'sometimes|required|in:ui_ux,front_end,back_end,tester,mobile',
            'join_date' => 'nullable|date',
            'birth_date' => 'nullable|date',
            'graduation_year' => 'nullable|integer|min:1900|max:' . date('Y'),
        ]);

        $imagePath = $request->hasFile('image')
            ? ImageService::update($request->file('image'), $employee->image, 'employee_images') 
            : $employee->image;
    
        $coverPhotoPath = $request->hasFile('cover_photo') 
            ? ImageService::update($request->file('cover_photo'), $employee->cover_photo, 'employee_cover_photos') 
            : $employee->cover_photo;
    
        $updated = $employee->update([
            'name' => $request->name ?? $employee->name,
            'phone' => $request->phone ?? $employee->phone,
            'email' => $request->email ?? $employee->email,
            'image' => $imagePath ? asset($imagePath) : $employee->image,
            'cover_photo' => $coverPhotoPath ? asset($coverPhotoPath) : $employee->cover_photo,
            'intro' => $request->intro ?? $employee->intro,
            'role' => $request->role ?? $employee->role,
            'join_date' => $request->join_date ?? $employee->join_date,
            'birth_date' => $request->birth_date ?? $employee->birth_date,
            'graduation_year' => $request->graduation_year ?? $employee->graduation_year,
        ]);

        if ($updated) {
            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully.',
                'data' => $employee->makeHidden(['password', 'remember_token'])
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
            'device_token' => 'nullable|string|max:255',
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
                if ($request->filled('device_token')) {
                    $employee->device_token = $request->device_token;
                    $employee->save();
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
        $Employee = auth('employee')->user()?->load('address','documents'); 

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
            'phone' => 'required|exists:employees,phone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Phone number does not exist.',
                'data' => null
            ], 404);
        }

        $phone = $request->phone;
        $otp = 1234; // يمكنك استبداله بـ rand(1000, 9999) للإنتاج

        // تخزين OTP لكل رقم هاتف بشكل منفصل
        Cache::put('otp_' . $phone, $otp, now()->addMinutes(10));

        // إرسال الـ OTP هنا إن كنت تستخدم SMS فعليًا

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully. Please check your phone.',
            'data' => null
        ], 200);
    }


    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
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

        $storedOtp = Cache::get('otp_' . $phone);

        if (!$storedOtp || $storedOtp != $otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => null
            ], 402);
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP verified successfully. You can now reset your password.',
            'data' => null
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
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

        // $storedOtp = Cache::get('otp');

        // if (!$storedOtp) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'OTP has either expired or was not verified.',
        //         'data' => null
        //     ], 402);
        // }

        $Employee = Employee::where('phone', $phone)->first();

        if (!$Employee) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found.',
                'data' => null
            ], 404);
        }

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
            'new_password' => 'required|min:6|confirmed', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 402);
        }

        $Employee = auth('Employee')->user();

        if (!$Employee) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Employee not found.',
                'data' => null
            ], 401);
        }

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

        $validator = Validator::make($request->all(), [
            'new_phone' => 'required|unique:employees,phone', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 402);
        }

        $Employee = auth('Employee')->user();

        if (!$Employee) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Employee not found.',
                'data' => null
            ], 401);
        }

        $otp = 1234;


        Cache::put('otp_change_phone_' . $Employee->id, [
            'otp' => $otp,
            'new_phone' => $request->new_phone,
        ], now()->addMinutes(10)); 

       
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

        $Employee = auth('Employee')->user();

        if (!$Employee) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Employee not found.',
                'data' => null
            ], 401);
        }

        $cachedData = Cache::get('otp_change_phone_' . $Employee->id);

        if (!$cachedData || $cachedData['otp'] != $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP.',
                'data' => null,
            ], 402);
        }

        
        $Employee->phone = $cachedData['new_phone'];
        $Employee->save();

        Cache::forget('otp_change_phone_' . $Employee->id);

        return response()->json([
            'status' => true,
            'message' => 'Phone number updated successfully.',
            'data' => [
                'phone' => $Employee->phone,
            ],
        ], 200);
    }

    public function getAllEmployees(Request $request)
    {
        try {
            $taskId = $request->task_id;

            if (!$taskId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Task ID is required.',
                    'data' => null,
                ], 400);
            }

            $currentEmployeeId = auth('employee')->id();

            $employees = Employee::whereHas('taskAssignments', function ($query) use ($taskId) {
                $query->where('task_id', $taskId);
            })
            ->where('id', '!=', $currentEmployeeId)
            ->get();

            return response()->json([
                'status' => true,
                'message' => 'Filtered employees retrieved successfully.',
                'data' => $employees,
            ], 200);

        } catch (\Exception $e) {  
            Log::error('Error fetching filtered employees: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching employees.',
                'data' => null,
            ], 500);
        }
    }

        public function searchByName(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        $employees = Employee::where('name', 'LIKE', '%' . $request->name . '%')->get();

        return response()->json([
            'status' => true,
            'message' => 'Search results.',
            'data' => $employees,
        ]);
    }
}
