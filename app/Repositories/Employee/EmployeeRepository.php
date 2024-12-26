<?php

namespace App\Repositories\Employee;

use App\Models\Employee;
use App\Repositories\Employee\EmployeeRepositoryInterface;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

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
            return ['error' => 'Employee not found'];
        }

        // Implement your password reset logic here (e.g., sending reset email or SMS)
        return ['message' => 'Password reset link sent'];
    }
}
