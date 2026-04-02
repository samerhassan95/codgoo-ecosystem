<?php

namespace App\Repositories\Client;

use App\Models\Client;
use App\Repositories\Client\ClientRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class ClientRepository implements ClientRepositoryInterface
{
    public function register(array $data)
    {
        $client = Client::create([
            'username'      => $data['username'],
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'password'      => Hash::make($data['password']),
            'photo'         => $data['photo'] ?? null,
            'company_name'  => $data['company_name'] ?? null,
            'website'       => $data['website'] ?? null,
            'address'       => $data['address'] ?? null,
            'city'          => $data['city'] ?? null,
            'country'       => $data['country'] ?? null,
            'role'          => $data['role'] ?? 'client', // optional
        ]);

        // Token WITH client type
        $token = JWTAuth::claims([
            'type' => 'client',
            'role' => $client->role,
        ])->fromUser($client);

        return [
            'client' => $client,
            'token'  => $token,
        ];
    }

    public function login(array $credentials)
    {
        // LOGIN THROUGH CLIENT GUARD
        if (!$token = auth('client')->attempt($credentials)) {
            return ['error' => 'Unauthorized'];
        }

        $client = auth('client')->user();

        // Issue token again with embedded payload
        $token = JWTAuth::claims([
            'type' => 'client',
            'role' => $client->role,
        ])->fromUser($client);

        return [
            'token'  => $token,
            'client' => $client
        ];
    }

    public function logout()
    {
        try {
            $token = JWTAuth::getToken();

            if ($token) {
                JWTAuth::invalidate($token);
            }

            return [
                'status'  => 'success',
                'message' => 'Successfully logged out'
            ];

        } catch (TokenBlacklistedException $e) {
            return [
                'status' => 'success',
                'message' => 'Token already blacklisted'
            ];
        } catch (TokenInvalidException $e) {
            return [
                'status' => 'success',
                'message' => 'Token invalid'
            ];
        } catch (JWTException $e) {
            return [
                'status' => 'success',
                'message' => 'Token error'
            ];
        }
    }

    public function forgotPassword($phone)
    {
        $client = Client::where('phone', $phone)->first();

        if (!$client) {
            return ['error' => 'Client not found'];
        }

        return ['message' => 'Password reset instructions sent'];
    }

    public function getAllClients()
    {
        return Client::all();
    }

    /**
     * Return the currently authenticated client or null.
     *
     * Tries auth('client') guard first, then falls back to JWT token parsing.
     *
     * @return \App\Models\Client|null
     */
    public function getAuthenticatedClient(): ?Client
    {
        // Try Laravel guard first
        try {
            $client = auth('client')->user();
            if ($client instanceof Client) {
                return $client;
            }
        } catch (\Throwable $e) {
            // ignore and try JWT parsing next
        }

        // Fallback to JWTAuth parseToken()->authenticate()
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return null;
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return null;
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}