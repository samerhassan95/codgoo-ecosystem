<?php

namespace App\Repositories\Employee;

use App\Models\Employee;
use App\Repositories\Employee\EmployeeRepositoryInterface;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function register(array $data)
    {
        $employee = Employee::create([
            'username' => $data['username'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);

        $token = JWTAuth::fromUser($employee);

        return [
            'Employee' => $employee,
            'token' => $token,
        ];
    }

    public function login(array $credentials)
    {
        if ($token = JWTAuth::attempt($credentials)) {
            return ['token' => $token];
        }

        return ['error' => 'Unauthorized'];
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return ['message' => 'Successfully logged out'];
    }

    public function forgotPassword($phone)
    {
        $employee = Employee::where('phone', $phone)->first();
        if (!$employee) {
            return [
                'status' => false,
                'message' => 'Employee not found',
                'data' => null
            ];
        }

        // Generate OTP for testing/development
        $otp = 1234;
        
        // Store OTP in cache for 10 minutes
        Cache::put('forgot_password_otp_' . $phone, $otp, now()->addMinutes(10));

        return [
            'status' => true,
            'message' => 'OTP sent successfully for password reset',
            'data' => [
                'otp' => $otp  // إضافة OTP للـ response للتطوير والاختبار
            ]
        ];
    }
}
