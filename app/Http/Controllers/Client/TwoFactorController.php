<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Models\Client;
use App\Models\ClientTwoFactorSetting;
use App\Mail\OtpMail;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $client = auth('client')->user();
        $settings = ClientTwoFactorSetting::firstOrCreate(['client_id' => $client->id]);
        return response()->json(['status' => true, 'data' => $settings], 200);
    }

public function enable(Request $request)
{
    $request->validate([
        'method' => 'required|in:email,sms,authenticator'
    ]);

    $client = auth('client')->user();

    $settings = ClientTwoFactorSetting::updateOrCreate(
        ['client_id' => $client->id],
        ['method' => $request->method, 'enabled' => false] // unverified until code confirmed
    );

    if ($request->method === 'email') {
        $otp = rand(1000, 9999);
        Cache::put('2fa_'.$client->id, $otp, now()->addMinutes(10));

        try {
            Mail::to($client->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            \Log::error("2FA email failed: ".$e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to send verification email.'
            ], 500);
        }

        // Return OTP in response for testing purposes
        return response()->json([
            'status' => true,
            'message' => '2FA code sent to email. Verify to enable.',
            'otp' => $otp // <--- For testing only
        ], 200);
    }

    if ($request->method === 'authenticator') {
        $settings->secret = Str::random(32);
        $settings->save();

        return response()->json([
            'status' => true,
            'message' => 'Scan QR in app to verify',
            'data' => ['secret' => $settings->secret]
        ], 200);
    }

    return response()->json([
        'status' => true,
        'message' => '2FA enable initiated. Verify to activate.'
    ], 200);
}


    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|numeric']);
        $client = auth('client')->user();
        $settings = ClientTwoFactorSetting::where('client_id',$client->id)->firstOrFail();

        $cached = Cache::get('2fa_'.$client->id);
        if ($settings->method === 'email') {
            if (!$cached || $cached != $request->code) {
                return response()->json(['status'=>false,'message'=>'Invalid or expired code.'], 422);
            }
            $settings->enabled = true;
            $settings->save();
            Cache::forget('2fa_'.$client->id);
            return response()->json(['status'=>true,'message'=>'Two-factor enabled.'],200);
        }

        // authenticator verification would verify TOTP against secret (not included here)
        return response()->json(['status'=>false,'message'=>'Unsupported method verification.'], 400);
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        $client = auth('client')->user();
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $client->password)) {
            return response()->json(['status'=>false,'message'=>'Invalid password.'], 401);
        }
        $settings = ClientTwoFactorSetting::where('client_id',$client->id)->first();
        if ($settings) {
            $settings->enabled = false;
            $settings->save();
        }
        return response()->json(['status'=>true,'message'=>'Two-factor disabled.'],200);
    }
}