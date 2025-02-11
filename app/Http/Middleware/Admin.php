<?php

namespace App\Http\Middleware;

use App\Enum\SettingStatus;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class Admin
{
    public function handle(Request $request, Closure $next)
    {


       
        $apiPassword = $request->header('API-Password');
        $API_PASSWORD="Nf:upZTg^7A?Hj";

        if ($apiPassword==null) {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed',
            ], 403);
        }
        elseif (trim($apiPassword) !== $API_PASSWORD)  {
            return response()->json([
                'status' => false,
                'message' => 'Invalid API password',
            ], 403);
        }
        try {
            config(['auth.defaults.guard' => 'admin']);
            
            $user = JWTAuth::parseToken()->authenticate();

            $token = JWTAuth::getToken();
            $payload = JWTAuth::getPayload($token)->toArray();

            if ($payload['type'] !== 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Not authorized',
                ], 403); 
            }

            if ($user->status == SettingStatus::getDisabled()) {
                return response()->json([
                    'status' => false,
                    'message' => __('site.Contact with Adminstration Your are Block'),
                ], 403); 
            }

        } catch (Exception $e) {
            if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token is Invalid',
                ], 401);
            } else if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'status' => false,
                    'message' => 'Token is Expired',
                ], 401); 
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Authorization Token not found',
                ], 401); 
            }
        }

        return $next($request);
    }
}
