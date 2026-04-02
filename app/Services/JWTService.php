<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Carbon;

class JWTService
{
    private string $privateKey;
    private string $publicKey;

    public function __construct()
    {
        $keyPath = storage_path('keys/marketplace_private.pem');

        if (!file_exists($keyPath)) {
            throw new \Exception('JWT private key not found');
        }

        $this->privateKey = file_get_contents($keyPath);

        if (!openssl_pkey_get_private($this->privateKey)) {
            throw new \Exception('Invalid JWT private key');
        }
    }

    /**
     * Create a JWT token for a user
     */
    public function createToken($user): string
    {
        $now = Carbon::now()->timestamp;
        $exp = Carbon::now()->addMinutes(env('JWT_TTL'))->timestamp;

        $payload = [
            'iss'  => config('app.url'),   // issuer
            'aud'  => config('app.url'),   // audience
            'iat'  => $now,                // issued at
            'nbf'  => $now,                // not before
            'exp' => Carbon::now()->addMinutes(config('app.jwt_ttl', 5256000))->timestamp,
            'jti'  => bin2hex(random_bytes(16)), // unique token id
            'uid'  => $user->id,           // user ID
            'role' => $user->role,         // optional: user role
        ];

        return JWT::encode($payload, $this->privateKey, 'RS256');
    }

    /**
     * Create an SSO token for marketplace app access
     * Embeds user info, permissions, and subscription details
     */
    public function createSSOToken($client, $app, $subscription): string
    {
        $payload = [
            'iss' => 'marketplace',
            'sub' => (string) $client->id,

            // informational only
            'email' => $client->email ?? null,
            'name'  => $client->username ?? $client->email ?? null,

            // target app
            'app_id' => $app->id,

            // HARD subscription enforcement
            'subscription_ends' => Carbon::parse(
                $subscription->ends_at
            )->timestamp,

            // timing
            'iat' => time(),
            'exp' => Carbon::now()->addMinutes(config('app.jwt_ttl', 5256000))->timestamp,

            'jti' => bin2hex(random_bytes(16)),
        ];

        return JWT::encode($payload, $this->privateKey, 'RS256');
    }


    /**
     * Map subscription plan to permissions array
     */
    private function mapPlanToPermissions(string $plan): array
    {
        return match (strtolower($plan)) {
            'basic' => ['read'],
            'premium' => ['read', 'write'],
            'enterprise' => ['admin', 'read', 'write', 'delete'],
            default => ['read'],
        };
    }

    /**
     * Map subscription plan to role
     */
    private function mapPlanToRole(string $plan): string
    {
        return match (strtolower($plan)) {
            'basic' => 'user',
            'premium' => 'power_user',
            'enterprise' => 'admin',
            default => 'user',
        };
    }

    /**
     * Verify and decode a JWT token
     */
    public function verifyToken(string $token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->publicKey, 'RS256'));
            return $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get user ID from token
     */
    public function getUserId(string $token)
    {
        $decoded = $this->verifyToken($token);
        return $decoded ? $decoded->uid : null;
    }
}
