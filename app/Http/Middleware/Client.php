<?php

namespace App\Http\Middleware;

use App\Enum\SettingStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
class Client
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
            config(['auth.defaults.guard' => 'client']);
            $user = JWTAuth::parseToken()->authenticate();
            $token = JWTAuth::getToken();
            $payload = JWTAuth::getPayload($token)->toArray();
            if ($payload['type'] != 'client') {
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
