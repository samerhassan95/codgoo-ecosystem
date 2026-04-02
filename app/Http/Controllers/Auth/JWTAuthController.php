<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JWTService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class JWTAuthController extends Controller
{
    protected JWTService $jwt;

    public function __construct(JWTService $jwt)
    {
        $this->jwt = $jwt;
    }

    /**
     * Login user and return JWT token
     */
public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
        'device'   => 'nullable|in:web,mobile',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    $isMobile = $request->device === 'mobile';

    $token = $this->jwt->createToken($user, [
        'exp' => $isMobile
            ? now()->addYears(10)->timestamp
            : now()->addHours(2)->timestamp,
    ]);

    return response()->json([
        'token' => $token,
        'user'  => [
            'id'    => $user->id,
            'email' => $user->email,
            'role'  => $user->role,
        ]
    ]);
}

}
