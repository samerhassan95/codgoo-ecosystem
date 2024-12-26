<?php

namespace App\Http\Middleware;

use App\Enum\SettingStatus;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
class Employee
{

    public function handle(Request $request, Closure $next)
    {
        try {
            config(['auth.defaults.guard' => 'employee']);
            $user = JWTAuth::parseToken()->authenticate();
            $token = JWTAuth::getToken();
            $payload = JWTAuth::getPayload($token)->toArray();
            if ($payload['type'] != 'employee') {
                return response()->json([
                    'status'=>false,
                    'message'=>'Not authorized',
                ],400);
            }
            if($user->status==SettingStatus::getDisabled()){
                return response()->json([
                    'status'=>false,
                    'message' =>__('site.Contact with Adminstration Your are Block'),
                ]);
            }
        } catch (Exception $e) {
            if ($e instanceof TokenInvalidException ) {
                return response()->json([
                    'status'=>false,
                    'message'=>'Token is Invalid',
                ],400);
            } else if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'status'=>false,
                    'message'=>'Token is Expired',
                ],400);
            }

            else {
                return response()->json([
                    'status'=>false,
                    'message'=>'Authorization Token not found',
                ],400);
            }
        }

        return $next($request);
    }
}
