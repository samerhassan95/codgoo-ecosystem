<?php

namespace App\Repositories\Client;

interface ClientRepositoryInterface
{
    public function register(array $data);
    public function login(array $credentials);
    public function logout();
    public function forgotPassword($phone);
    public function getAllClients();

}
