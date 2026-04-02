<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountShare;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AccountShareController extends Controller
{
    public function index()
    {
        $client = auth('client')->user();
        $shares = AccountShare::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['status' => true, 'data' => $shares], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|string|in:admin,editor,viewer',
            'dashboards' => 'required|array|min:1',
            'dashboards.*.name' => 'required|string',
            'dashboards.*.enabled' => 'required|boolean',
            'dashboards.*.services' => 'sometimes|array',
            'dashboards.*.services.*.name' => 'required|string',
            'dashboards.*.services.*.enabled' => 'required|boolean'
        ]);

        $client = auth('client')->user();

        // Check if email already has access
        $existingShare = AccountShare::where('client_id', $client->id)
            ->where('email', $request->email)
            ->where('status', '!=', 'revoked')
            ->first();

        if ($existingShare) {
            return response()->json([
                'status' => false,
                'message' => 'This email already has access to your account.'
            ], 422);
        }

        $inviteCode = Str::random(32);

        $share = AccountShare::create([
            'client_id' => $client->id,
            'email' => $request->email,
            'role' => $request->role,
            'dashboards' => $request->dashboards,
            'status' => 'pending',
            'invite_code' => $inviteCode,
        ]);

        // TODO: Send invite email
        // Mail::to($request->email)->send(new AccountShareInviteMail($client, $share, $inviteCode));

        return response()->json([
            'status' => true,
            'message' => 'Invitation sent successfully.',
            'data' => $share
        ], 201);
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'role' => 'sometimes|string|in:admin,editor,viewer',
            'dashboards' => 'sometimes|array',
            'dashboards.*.name' => 'required|string',
            'dashboards.*.enabled' => 'required|boolean',
            'dashboards.*.services' => 'sometimes|array',
            'dashboards.*.services.*.name' => 'required|string',
            'dashboards.*.services.*.enabled' => 'required|boolean',
            'status' => 'sometimes|string|in:pending,accepted,revoked'
        ]);

        $client = auth('client')->user();
        $share = AccountShare::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();

        $share->fill($request->only(['role', 'dashboards', 'status']));
        $share->save();

        return response()->json([
            'status' => true,
            'message' => 'Account sharing updated successfully.',
            'data' => $share
        ], 200);
    }

    public function destroy($id)
    {
        $client = auth('client')->user();
        $share = AccountShare::where('id', $id)
            ->where('client_id', $client->id)
            ->firstOrFail();
        
        $share->delete();

        return response()->json([
            'status' => true,
            'message' => 'Account sharing removed successfully.'
        ], 200);
    }

    public function acceptInvite(Request $request)
    {
        $request->validate([
            'invite_code' => 'required|string'
        ]);

        $share = AccountShare::where('invite_code', $request->invite_code)
            ->where('status', 'pending')
            ->firstOrFail();

        $share->update(['status' => 'accepted']);

        return response()->json([
            'status' => true,
            'message' => 'Invitation accepted successfully.',
            'data' => $share
        ], 200);
    }
}