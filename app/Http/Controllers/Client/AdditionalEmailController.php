<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class AdditionalEmailController extends Controller
{
    public function index()
    {
        $client = auth('client')->user();
        $emails = ClientEmail::where('client_id', $client->id)->get();
        return response()->json(['status'=>true,'data'=>$emails],200);
    }

    public function store(Request $request)
    {
        $request->validate(['email' => 'required|email|unique:client_emails,email']);
        $client = auth('client')->user();
        $code = rand(1000,9999);
        $email = ClientEmail::create([
            'client_id' => $client->id,
            'email' => $request->email,
            'verification_code' => $code,
            'verified' => false
        ]);
        // send code
        Mail::to($request->email)->send(new OtpMail($code));
        return response()->json(['status'=>true,'message'=>'Verification code sent to new email.','data'=>['id'=>$email->id],'verification_code' => $code],200);
    }

    public function verify($id, Request $request)
    {
        $request->validate(['otp' => 'required|numeric']);
        $client = auth('client')->user();
        $email = ClientEmail::where('id',$id)->where('client_id',$client->id)->firstOrFail();
        if ($email->verification_code != $request->otp) {
            return response()->json(['status'=>false,'message'=>'Invalid code'], 422);
        }
        $email->verified = true;
        $email->verified_at = now();
        $email->verification_code = null;
        $email->save();
        return response()->json(['status'=>true,'message'=>'Email verified successfully.'],200);
    }

    public function destroy($id)
    {
        $client = auth('client')->user();
        $email = ClientEmail::where('id',$id)->where('client_id',$client->id)->firstOrFail();
        $email->delete();
        return response()->json(['status'=>true,'message'=>'Email removed.'],200);
    }
}