<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marketplace SSO Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for JWT-based Single Sign-On for marketplace apps
    |
    */

    /**
     * JWT Key Paths
     * These should match your .env settings
     */
    'jwt_private_key' => env('JWT_PRIVATE_KEY_PATH', 'keys/private_key.pem'),
    'jwt_public_key' => env('JWT_PUBLIC_KEY_PATH', 'keys/public_key.pem'),

    /**
     * Token Expiration (in minutes)
     */
    'token_ttl' => env('JWT_SSO_TTL', 60), // 1 hour default

    /**
     * Permission Mappings
     * Maps subscription plan names to permissions and roles
     */
    'permission_mappings' => [
        'basic' => [
            'role' => 'user',
            'permissions' => ['read'],
        ],
        'premium' => [
            'role' => 'power_user',
            'permissions' => ['read', 'write'],
        ],
        'enterprise' => [
            'role' => 'admin',
            'permissions' => ['admin', 'read', 'write', 'delete'],
        ],
    ],

    /**
     * Default fallback if plan not found
     */
    'default_plan' => 'basic',

    /**
     * SSO Entry Point
     * Default path on child apps for SSO login
     */
    'default_sso_entrypoint' => '/auth/sso-login',

];
