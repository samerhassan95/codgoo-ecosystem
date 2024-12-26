<?php

namespace App\Repositories\Employee;

interface EmployeeRepositoryInterface
{
    public function register(array $data);
    public function login(array $credentials);
    public function logout();
    public function forgotPassword($phone);
}