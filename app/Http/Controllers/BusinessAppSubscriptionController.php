<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceApp;
use App\Models\BusinessAppSubscription;
use App\Models\BusinessAppPlan;
use App\Models\BusinessAppSubscriptionPayment;

class BusinessAppSubscriptionController extends Controller
{
    /**
     * Subscribe to a business app plan.
     */
    public function subscribe(Request $request)
    {
        $client = auth()->user();

        $validated = $request->validate([
            'service_app_id' => 'required|exists:service_apps,id',
            'plan_id' => 'required|exists:business_app_plans,id',
        ]);

        // Fetch the service app and check type
        $app = ServiceApp::findOrFail($validated['service_app_id']);
        if ($app->type !== 'Bussiness') {
            return response()->json([
                'status' => false,
                'message' => 'You can only subscribe to Business apps.',
                'app' => $app->name
            ], 403);
        }

        $plan = BusinessAppPlan::findOrFail($validated['plan_id']);

        $subscription = BusinessAppSubscription::create([
            'customer_id' => $client->id,
            'service_app_id' => $app->id,
            'business_app_plan_id' => $plan->id,
            'status' => 'pending',
            'started_at' => now(),
            'expires_at' => now()->addDays($plan->duration_days),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Subscription created. Upload payment.',
            'subscription_id' => $subscription->id
        ], 201);
    }

    /**
     * Upload payment for a pending subscription.
     */
    public function uploadPayment(Request $request, $subscriptionId)
    {
        $client = auth()->user();

        $subscription = BusinessAppSubscription::where('id', $subscriptionId)
            ->where('customer_id', $client->id)
            ->where('status', 'pending')
            ->firstOrFail();

        if ($subscription->payment) {
            return response()->json([
                'status' => false,
                'message' => 'Payment already uploaded.'
            ], 409);
        }

        $validated = $request->validate([
            'attachment' => 'required|file|mimes:jpg,png,pdf|max:5120'
        ]);

        $path = $request->file('attachment')->store('business_app_payments', 'public');

        BusinessAppSubscriptionPayment::create([
            'business_app_subscription_id' => $subscription->id,
            'attachment_url' => asset('storage/'.$path),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payment uploaded. Waiting for admin approval.'
        ]);
    }

    /**
     * Admin approves/rejects a subscription.
     */
    public function approve(Request $request, BusinessAppSubscription $subscription)
    {
        $admin = auth('admin')->user();

        $validated = $request->validate([
            'approve' => 'required|boolean'
        ]);

        if ($subscription->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Already processed.'
            ], 409);
        }

        $subscription->update([
            'status' => $validated['approve'] ? 'active' : 'rejected',
            'is_approved' => $validated['approve'],
            'approved_at' => now(),
            'approved_by' => $admin->id
        ]);

        return response()->json([
            'status' => true,
            'message' => $validated['approve']
                ? 'Subscription approved.'
                : 'Subscription rejected.'
        ]);
    }

    /**
     * List all available plans.
     */
    public function getAllPlans()
    {
        $plans = BusinessAppPlan::all();

        return response()->json([
            'status' => true,
            'plans' => $plans
        ]);
    }
}
