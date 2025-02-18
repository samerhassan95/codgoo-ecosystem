<?php

namespace App\Repositories\Client;

use App\Models\Client;
use App\Repositories\Client\ClientRepositoryInterface;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

class ClientRepository implements ClientRepositoryInterface
{
    public function register(array $data)
    {
        $client = Client::create([
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'photo' => $data['photo'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'website' => $data['website'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
        ]);

        $token = JWTAuth::fromUser($client);

        return [
            'client' => $client,
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
        $client = Client::where('phone', $phone)->first();
        if (!$client) {
            return ['error' => 'Client not found'];
        }

        return ['message' => 'Password reset link sent'];
    }


    public function getAllClients()
    {
        return Client::all(); 
    }

}
