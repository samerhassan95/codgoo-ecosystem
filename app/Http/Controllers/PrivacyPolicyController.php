<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PrivacyPolicy;
use Illuminate\Support\Facades\Validator;
class PrivacyPolicyController extends Controller
{

    public function index()
    {
        $privacyPolicy = PrivacyPolicy::first();
    
        if ($privacyPolicy) {
            return response()->json([
                'content' => $privacyPolicy->content
            ]);
        }
    
        return response()->json(['message' => 'Privacy Policy not found'], 404);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $privacyPolicy = PrivacyPolicy::updateOrCreate(
            [
                'content' => $request->content,
            ]
        );

        return response()->json([
            'message' => 'Privacy Policy created/updated successfully',
            'data' => $privacyPolicy,
        ], 201);
    }
}
