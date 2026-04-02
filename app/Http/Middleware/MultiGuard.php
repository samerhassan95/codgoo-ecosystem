<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MultiGuard
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $request->auth_user = Auth::guard($guard)->user();
                $request->auth_guard = $guard;
                return $next($request);
            }
        }

        return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
    }
}
