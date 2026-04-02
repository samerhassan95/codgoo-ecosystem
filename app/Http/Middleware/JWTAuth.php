<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JWTService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JWTAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Get token from Bearer header or session
        $rawToken = $request->bearerToken() ?? session('jwt_token');
        
        if (!$rawToken) {
            return $this->handleMissingToken($request);
        }

        $jwt = app(JWTService::class);
        $result = $jwt->verifyToken($rawToken);

        if (!$result['valid']) {
            return $this->handleInvalidToken($request, $result['error']);
        }

        $decoded = $result['data'];
        $userId = $decoded->uid ?? null;

        if (!$userId) {
            return $this->handleInvalidToken($request, 'invalid_payload');
        }

        $user = User::find($userId);
        
        if (!$user) {
            return $this->handleInvalidToken($request, 'user_not_found');
        }

        // Log user in for this request
        Auth::login($user);

        return $next($request);
    }

    /**
     * Handle missing token based on request type
     */
    protected function handleMissingToken(Request $request)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['error' => 'Missing token'], 401);
        }

        // Web request - redirect to login
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        
        return redirect()->route('login')->with('error', 'Please login to continue.');
    }

    /**
     * Handle invalid or expired token based on request type
     */
    protected function handleInvalidToken(Request $request, $reason)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            $messages = [
                'token_expired' => 'Token has expired',
                'invalid_signature' => 'Invalid token signature',
                'invalid_token' => 'Invalid token',
                'invalid_payload' => 'Invalid token payload',
                'user_not_found' => 'User not found',
            ];
            
            return response()->json([
                'error' => $messages[$reason] ?? 'Invalid or expired token'
            ], 401);
        }

        // Web request - redirect to login
        Auth::logout();
        session()->forget('jwt_token');
        session()->invalidate();
        session()->regenerateToken();

        $messages = [
            'token_expired' => 'Your session has expired. Please login again.',
            'invalid_signature' => 'Invalid authentication. Please login again.',
            'invalid_token' => 'Invalid authentication. Please login again.',
            'invalid_payload' => 'Authentication error. Please login again.',
            'user_not_found' => 'User account not found. Please login again.',
        ];

        return redirect()->route('login')->with('error', $messages[$reason] ?? 'Please login again.');
    }
}