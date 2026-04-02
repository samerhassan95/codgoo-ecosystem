<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BundlePackage;
use App\Models\Invoice;
use App\Models\BundlePackagePrice;
use App\Models\CustomBundle;
use App\Models\Payment;
use App\Models\ServiceApp;
use App\Models\Client;
use App\Models\BusinessAppSubscription;
use App\Http\Resources\BundlePackageResource;
use App\Http\Resources\CustomBundleResource;
use App\Http\Resources\CustomBundleDetailResource;
use App\Http\Resources\BundleComparisonResource;
use App\Http\Requests\StoreCustomBundleRequest;
use App\Http\Requests\UpdateCustomBundleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\PayPalService;
use App\Http\Resources\ServiceAppResource;

class MarketplaceController extends Controller
{
    // ✅ Constructor now does NOT use Sanctum
    public function __construct()
    {
        // Let your 'client' middleware handle authentication
    }

    // ----------------- Package Listing -----------------
    public function indexPackages()
    {
        $packages = BundlePackage::all();
        return BundlePackageResource::collection($packages);
    }

    public function showPackage(BundlePackage $package)
    {
        return new BundlePackageResource($package);
    }



////dashboard/////



public function dashboard(Request $request)
{
    $client = auth()->user();
    if (!$client) {
        return response()->json(['status'=>false,'message'=>'Unauthenticated'],401);
    }

    $now = now();

    // 1️⃣ All active bundles
    $bundles = CustomBundle::with(['applications','price'])
        ->where('customer_id', $client->id)
        ->where('status','active')
        ->get();

    // 2️⃣ All active business subscriptions
    $businessSubscriptions = BusinessAppSubscription::with(['app','plan'])
        ->where('customer_id',$client->id)
        ->where('status','active')
        ->get();

    // 3️⃣ Summary counts
    $allAppsCount = $bundles->sum(fn($b) => $b->applications->count()) 
        + $businessSubscriptions->count();

    $activeSubscriptionsCount = $bundles->count() + $businessSubscriptions->count();

    $appTypesCount = $bundles->pluck('applications.*.type')
        ->flatten()
        ->merge($businessSubscriptions->pluck('app.type'))
        ->unique()
        ->count();

    $totalRevenue = $bundles->sum(fn($b)=> $b->price->amount ?? 0)
        + $businessSubscriptions->sum(fn($b)=> $b->plan->price_amount ?? 0);

    // 4️⃣ Subscriptions overview (last 4)
    $subscriptionsOverview = collect();

    foreach ($bundles->take(4) as $bundle) {
        foreach ($bundle->applications as $app) {
            $subscriptionsOverview->push([
                'name' => $app->name,
                'type' => $app->type,
'expiry' => $bundle->expires_at ? Carbon::parse($bundle->expires_at)->format('d M Y') : null,
                'category' => $app->category,
                'icon_url' => $app->icon_url,
            ]);
        }
    }

    foreach ($businessSubscriptions->take(4) as $sub) {
        $app = $sub->app;
        $subscriptionsOverview->push([
            'name' => $app->name,
            'type' => $app->type,
'expiry' => $sub->expires_at ? Carbon::parse($sub->expires_at)->format('d M Y') : null,
            'category' => $app->category ?? 'Business',
            'icon_url' => $app->icon_url,
        ]);
    }

    // 5️⃣ Quick access (last 3)
    $quickAccess = collect();

    foreach ($bundles->take(3) as $bundle) {
        $quickAccess->push([
            'app_name' => $bundle->applications->first()?->name ?? 'N/A',
            'plan' => $bundle->price->name ?? 'Basic',
            'status' => 'Active',
'expiry' => $bundle->expires_at ? Carbon::parse($bundle->expires_at)->format('d M Y') : null,
]);
    }

    foreach ($businessSubscriptions->take(3) as $sub) {
        $quickAccess->push([
            'app_name' => $sub->app->name,
            'plan' => $sub->plan->name,
            'status' => 'Active',
'expiry' => $sub->expires_at ? Carbon::parse($sub->expires_at)->format('d M Y') : null,
        ]);
    }

    // 6️⃣ App categories
    $categories = $bundles->pluck('applications.*.category')->flatten()
        ->merge($businessSubscriptions->pluck('app.category'))->filter();
    $categoryCounts = $categories->countBy()->map(fn($count) => round($count / $categories->count() * 100, 0));

    // 7️⃣ Recent activity (dummy for now)
    $recentActivity = [
        ['message' => 'You renewed your subscription for FixMate App', 'time' => '3h ago'],
        ['message' => 'New update available for App Builder', 'time' => '5h ago'],
    ];

    return response()->json([
        'status' => true,
        'summary' => [
            'number_of_apps' => $allAppsCount,
            'active_subscriptions' => $activeSubscriptionsCount,
            'types_of_apps' => $appTypesCount,
            'total_revenue' => $totalRevenue,
        ],
        'subscriptions_overview' => $subscriptionsOverview,
        'quick_access' => $quickAccess,
        'app_categories' => $categoryCounts,
        'recent_activity' => $recentActivity,
    ]);
}

///////////billing//////
public function billingDashboard(Request $request)
{
    $client = auth()->user();
    if (!$client) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $invoices = collect();

    // -----------------------------
    // 🔵 CUSTOM BUNDLES
    // -----------------------------
    $bundles = CustomBundle::with(['price', 'payment', 'applications'])
        ->where('customer_id', $client->id)
        ->get();

    foreach ($bundles as $bundle) {
        $payment = $bundle->payment;

        $status = 'unpaid';
        $paidVia = 'unpaid';
        $statusText = 'open';

        if ($payment?->status === 'completed') {
            $status = 'paid';
            $paidVia = 'gateway';
            $statusText = 'closed';
        } elseif ($bundle->status === 'active') {
            $status = 'paid';
            $paidVia = 'offline';
            $statusText = 'closed';
        } elseif ($bundle->expires_at && now()->gt($bundle->expires_at)) {
            $status = 'overdue';
            $statusText = 'open';
        }

        $invoices->push([
            'parameter'      => 'App', // ✅ group under "App"
            'id'             => $bundle->id,
            'invoice_number' => 'INV-' . now()->year . '-' . str_pad($bundle->id, 6, '0', STR_PAD_LEFT),
            'type'           => 'bundle',
            'name'           => optional($bundle->applications)->pluck('name')->join(', '),
            'amount'         => (float) ($bundle->price?->amount ?? 0),
            'currency'       => 'EGP',
            'status'         => $status,
            'status_text'    => $statusText,
            'paid_via'       => $paidVia,
            'start_info'     => 'started 1 of ' . optional($bundle->applications)->count(),
            'due_date'       => $bundle->expires_at ? Carbon::parse($bundle->expires_at)->format('d M Y') : null,
        ]);
    }

    // -----------------------------
    // 🟣 BUSINESS SUBSCRIPTIONS
    // -----------------------------
    $businessSubs = BusinessAppSubscription::with(['plan', 'app', 'payment'])
        ->where('customer_id', $client->id)
        ->get();

    foreach ($businessSubs as $sub) {
        $payment = $sub->payment;

        $status = 'unpaid';
        $paidVia = 'unpaid';
        $statusText = 'open';

        if ($payment?->status === 'completed') {
            $status = 'paid';
            $paidVia = 'gateway';
            $statusText = 'closed';
        } elseif ($sub->status === 'active') {
            $status = 'paid';
            $paidVia = 'offline';
            $statusText = 'closed';
        } elseif ($sub->expires_at && now()->gt($sub->expires_at)) {
            $status = 'overdue';
            $statusText = 'open';
        }

        $invoices->push([
            'parameter'      => 'App', // ✅ group under "App"
            'id'             => $sub->id,
            'invoice_number' => 'INV-' . now()->year . '-' . str_pad($sub->id, 6, '0', STR_PAD_LEFT),
            'type'           => 'business',
            'name'           => optional($sub->app)->name ?? 'N/A',
            'amount'         => (float) ($sub->plan?->price_amount ?? 0),
            'currency'       => 'EGP',
            'status'         => $status,
            'status_text'    => $statusText,
            'paid_via'       => $paidVia,
            'start_info'     => 'bundle', 
            'due_date'       => $sub->expires_at ? Carbon::parse($sub->expires_at)->format('d M Y') : null,
        ]);
    }

    // -----------------------------
    // 📄 PROJECT INVOICES
    // -----------------------------
    $projectInvoices = Invoice::whereHas('project', fn($q) => $q->where('client_id', $client->id))
        ->with('project.client')
        ->get();

    foreach ($projectInvoices as $invoice) {
        $status = $invoice->status;
        if ($status === 'unpaid' && $invoice->due_date && Carbon::parse($invoice->due_date)->isPast()) {
            $status = 'overdue';
        }

        $invoices->push([
            'parameter'      => 'Software', // ✅ separate group
            'id'             => $invoice->id,
            'invoice_number' => 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT),
            'type'           => 'project',
            'name'           => $invoice->project?->name ?? 'N/A',
            'amount'         => (float) $invoice->amount,
            'currency'       => 'EGP',
            'status'         => $status,
            'status_text'    => $status === 'paid' ? 'closed' : 'open',
            'paid_via'       => $status === 'paid' ? 'gateway' : 'unpaid',
            'start_info'     => null,
            'due_date'       => $invoice->due_date ? Carbon::parse($invoice->due_date)->format('d M Y') : null,
        ]);
    }

    // -----------------------------
    // Summary counts
    // -----------------------------
    $summary = [
        'all'     => $invoices->count(),
        'paid'    => $invoices->where('status', 'paid')->count(),
        'unpaid'  => $invoices->where('status', 'unpaid')->count(),
        'overdue' => $invoices->where('status', 'overdue')->count(),
    ];

    return response()->json([
        'status'  => true,
        'summary' => $summary,
        'data'    => $invoices->sortByDesc('due_date')->values(),
    ]);
}




private function resolveInvoiceStatus($model, $payment = null)
{
    if ($payment) {
        if ($payment->status === 'completed') {
            return 'paid';   // online payment
        } elseif ($payment->status === 'active') {
            return 'paid';   // offline payment
        }
    }

    // 🔴 Expired
    if ($model->expires_at && now()->gt($model->expires_at)) {
        return 'overdue';
    }

    // ⚪ Unpaid
    return 'unpaid';
}







///////// attach apps to bundle 
public function attachAppsToBundle(Request $request, $bundlePackageId)
{
    $client = auth()->user();

    if (!$client) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated. Please login first.'
        ], 401);
    }
    // Validate input
    $validated = $request->validate([
        'applications' => 'required|array|min:1',
        'applications.*' => 'integer|exists:service_apps,id',
    ]);
    
        $appsToAttach = ServiceApp::whereIn('id', $validated['applications'])->get();

    // ✅ Check only General apps
    foreach ($appsToAttach as $app) {
        if ($app->type !== 'General') {
            return response()->json([
                'status' => false,
                'message' => 'Only General apps can be attached.',
                'app' => $app->name
            ], 403);
        }
    }

    // Find the client's active subscription for this bundle package
    $customBundle = CustomBundle::where('bundle_package_id', $bundlePackageId)
        ->where('customer_id', $client->id)
        ->where('status', 'active') // only active subscriptions allowed
        ->first();

    if (!$customBundle) {
        return response()->json([
            'status' => false,
            'message' => 'You do not have an active subscription for this bundle or your subscription is not approved yet.'
        ], 404);
    }

    $package = $customBundle->bundlePackage;
    if (!$package) {
        return response()->json([
            'status' => false,
            'message' => 'Associated bundle package not found.'
        ], 404);
    }

    // Check remaining app slots
    $currentAppCount = $customBundle->applications()->count();
    $remainingSlots = $package->apps_count - $currentAppCount;

    if ($remainingSlots <= 0) {
        return response()->json([
            'status' => false,
            'message' => 'No remaining app slots available for this bundle.'
        ], 400);
    }

    // Filter out apps already attached
    $alreadyAttached = $customBundle->applications()->pluck('id')->toArray();
    $appsToAttach = array_diff($validated['applications'], $alreadyAttached);

    if (empty($appsToAttach)) {
        return response()->json([
            'status' => false,
            'message' => 'All selected apps are already subscribed to this bundle.'
        ], 400);
    }

    // Limit apps to remaining slots
    $appsToAttach = array_slice($appsToAttach, 0, $remainingSlots);

    // Attach apps with error handling
    try {
        $customBundle->applications()->attach($appsToAttach);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to attach apps: ' . $e->getMessage()
        ], 500);
    }

    // Generate SSO tokens for external apps only
    $subscribedApps = ServiceApp::whereIn('id', $appsToAttach)->get();
    $jwtService = app(\App\Services\JWTService::class);
    $now = now();

    foreach ($subscribedApps as $app) {
        if ($app->is_external && $app->app_url) {
            try {
                $subscription = (object)[
                    'id' => $customBundle->id,
                    'plan_name' => $package->name ?? 'basic',
                    'ends_at' => $customBundle->expires_at,
                ];

                $token = $jwtService->createSSOToken($client, $app, $subscription);
                $profileUrl = rtrim($app->app_url, '/') . ($app->sso_entrypoint ?? '/auth/sso-login') . "?token=" . $token;

                DB::table('custom_bundle_service_app')
                    ->where('custom_bundle_id', $customBundle->id)
                    ->where('service_app_id', $app->id)
                    ->update([
                        'external_profile_url' => $profileUrl,
                        'updated_at' => $now,
                    ]);
            } catch (\Exception $e) {
                \Log::error('Failed generating SSO token for app ID ' . $app->id, [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    // Return updated bundle info
    $finalBundle = CustomBundle::with(['applications' => function ($query) {
        $query->withPivot('external_profile_url');
    }])->find($customBundle->id);

    return response()->json([
        'status' => true,
        'message' => 'Apps attached successfully.',
        'data' => new CustomBundleResource($finalBundle),
        'remaining_app_count' => $package->apps_count - $finalBundle->applications()->count()
    ], 200);
}


/////// store bundle
public function storeCustomBundle(StoreCustomBundleRequest $request)
{
    $validated = $request->validated();
    $client = auth()->user();
    if (!$client) {
        return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
    }
    
        $apps = ServiceApp::whereIn('id', $validated['applications'] ?? [])->get();
    foreach ($apps as $app) {
        if ($app->type !== 'General') {
            return response()->json([
                'status' => false,
                'message' => 'You can only subscribe to General apps.',
                'app' => $app->name
            ], 403);
        }
    }

    $package = BundlePackage::findOrFail($validated['bundleId']);
    $selectedPrice = BundlePackagePrice::findOrFail($validated['priceId']); // <-- dynamic pricing

    // Prevent duplicate pending subscription for same package & price
    $pendingSameBundle = CustomBundle::where('customer_id', $client->id)
        ->where('bundle_package_id', $package->id)
        ->where('bundle_price_id', $selectedPrice->id)
        ->where('status', 'pending')
        ->first();

    if ($pendingSameBundle) {
        return response()->json([
            'status' => false,
            'message' => 'You already have a pending subscription for this bundle and plan.'
        ], 409);
    }

    $customBundle = CustomBundle::create([
        'customer_id' => $client->id,
        'bundle_package_id' => $package->id,
        'bundle_price_id' => $selectedPrice->id,
        'total_price_amount' => $selectedPrice->amount,
        'total_price_currency' => $selectedPrice->currency,
        'status' => 'pending',
        'requested_app_ids' => $validated['applications'] ?? [],
        'is_approved' => false,
        'purchased_at' => now(),
        'expires_at' => now()->addDays($selectedPrice->duration_days),
        'meta' => [
            'plan_name' => $selectedPrice->name,
            'duration_days' => $selectedPrice->duration_days
        ]
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Subscription created. Please upload payment attachment.',
        'data' => new CustomBundleResource($customBundle),
        'attachment_upload_url' => route('marketplace.uploadAttachment', $package->id)
    ], 201);
}


    // ----------------- Update Custom Bundle -----------------
public function updateCustomBundle(UpdateCustomBundleRequest $request, $bundlePackageId)
{
    $client = auth()->user();
    $oldBundle = CustomBundle::where('customer_id', $client->id)
        ->where('bundle_package_id', $bundlePackageId)
        ->where('status', 'active')
        ->first();

    if (!$oldBundle) {
        return response()->json([
            'status' => false,
            'message' => 'Active subscription not found.'
        ], 404);
    }

    $validated = $request->validated();
    
$apps = ServiceApp::whereIn('id', $validated['applications'] ?? [])->get();    foreach ($apps as $app) {
        if ($app->type !== 'General') {
            return response()->json([
                'status' => false,
                'message' => 'You can only subscribe to General apps.',
                'app' => $app->name
            ], 403);
        }
    }

    $newPackage = BundlePackage::findOrFail($validated['bundleId']);
    $selectedPrice = BundlePackagePrice::findOrFail($validated['priceId']); // dynamic pricing

    // Check for pending upgrade
    $pendingUpgrade = CustomBundle::where('customer_id', $client->id)
        ->where('bundle_package_id', $newPackage->id)
        ->where('bundle_price_id', $selectedPrice->id)
        ->where('status', 'pending')
        ->first();

    if ($pendingUpgrade) {
        return response()->json([
            'status' => false,
            'message' => 'You already have a pending upgrade for this bundle and plan.'
        ], 409);
    }

    // ✅ Create new subscription (upgrade)
    $newBundle = CustomBundle::create([
        'customer_id' => $client->id,
        'bundle_package_id' => $newPackage->id,
        'bundle_price_id' => $selectedPrice->id,
        'total_price_amount' => $selectedPrice->amount,
        'total_price_currency' => $selectedPrice->currency,
        'status' => 'pending',
        'requested_app_ids' => $validated['applications'] ?? [],
        'is_approved' => false,
        'purchased_at' => now(),
        'expires_at' => now()->addDays($selectedPrice->duration_days),
        'meta' => [
            'plan_name' => $selectedPrice->name,
            'duration_days' => $selectedPrice->duration_days
        ]
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Upgrade request created. Please complete payment.',
        'data' => new CustomBundleResource($newBundle),
    ], 201);
}

///// admin approve
public function approveOfflineSubscription(Request $request, CustomBundle $customBundle)
{

    $admin = auth('admin')->user();

    if (!$admin) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated admin.'
        ], 401);
    }

    $validated = $request->validate([
        'approve' => ['required', 'boolean'],
    ]);

    // Ignore global scopes to fetch pending subscription
    $customBundle = CustomBundle::withoutGlobalScopes()->find($customBundle->id);
    if (!$customBundle) {
        return response()->json(['status'=>false, 'message'=>'Bundle not found'], 404);
    }

    if ($customBundle->status !== 'pending') {
        return response()->json([
            'status' => false,
            'message' => 'Subscription already processed.'
        ], 409);
    }

    if (!$customBundle->attachment_url) {
        return response()->json([
            'status' => false,
            'message' => 'No payment attachment uploaded.'
        ], 422);
    }

    $customBundle->update([
        'is_approved' => $validated['approve'],
        'status' => $validated['approve'] ? 'active' : 'rejected',
        'approved_at' => now(),
        'approved_by' => $admin->id,
    ]);
    
if ($validated['approve']) {
    $appIds = $customBundle->requested_app_ids ?? [];

    if (!empty($appIds)) {
        $customBundle->applications()->syncWithoutDetaching($appIds);
    }

    }
    \Log::info('Attached apps', [
    'bundle_id' => $customBundle->id,
    'apps' => $customBundle->applications()->pluck('service_apps.id')
]);

    return response()->json([
        'status' => true,
        'message' => $validated['approve']
            ? 'Subscription approved.'
            : 'Subscription rejected.',
        'data' => [
            'id' => $customBundle->id,
            'status' => $customBundle->status,
        ]
    ]);
}

////////////// upload attachment 
public function uploadPaymentAttachment(Request $request, $bundlePackageId)
{
    $client = auth()->user();

    if (!$client) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated.'
        ], 401);
    }

    // Find the client's pending subscription for this bundle package
    $customBundle = CustomBundle::where('bundle_package_id', $bundlePackageId)
        ->where('customer_id', $client->id)
        ->where('status', 'pending')
        ->latest()
        ->first();

    if (!$customBundle) {
        return response()->json([
            'status' => false,
            'message' => 'No pending subscription found for this bundle.'
        ], 404);
    }

    // Prevent re-upload
    if ($customBundle->attachment_url) {
        return response()->json([
            'status' => false,
            'message' => 'Payment attachment already uploaded.'
        ], 409);
    }

    // Validate file
    $validated = $request->validate([
        'attachment' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
    ]);

    // Store file in public disk
    $path = $request->file('attachment')->store('payment_attachments', 'public');

    // Generate full URL
    $fullUrl = asset('storage/' . $path);

    // Save to DB (make sure attachment_url column exists!)
    $customBundle->update([
        'attachment_url' => $fullUrl,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Attachment uploaded successfully. Waiting for admin approval.',
        'data' => [
            'bundle_package_id' => $bundlePackageId,
            'attachment_url' => $fullUrl,
            'status' => $customBundle->status,
        ]
    ], 200);
}




    
    // ----------------- Get My Subscribed Apps -----------------


public function mySubscribedApps(Request $request)
{
    $client = auth()->user();
    if (!$client) {
        return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
    }

    $jwtService = app(\App\Services\JWTService::class);
    $now = now();
    $apps = collect();

    /*
    |--------------------------------------------------------------------------
    | BUNDLES (UNCHANGED)
    |--------------------------------------------------------------------------
    */
    $bundles = CustomBundle::with([
        'price',
        'bundlePackage',
        'applications' => fn($q) => $q->withPivot('external_profile_url', 'deleted_at')
    ])
        ->where('customer_id', $client->id)
        ->where('status', 'active')
        ->get();

    $activeBundles = $bundles->filter(fn($bundle) => $bundle->is_active);

    foreach ($activeBundles as $bundle) {
        foreach ($bundle->applications as $app) {

            if ($request->filled('type') && !in_array($app->type, ['General', 'Master'])) {
                continue;
            }

            $launchUrl = $app->pivot->external_profile_url;

            if ($app->is_external && $app->app_url && !$launchUrl) {
                $subscription = (object)[
                    'id' => $bundle->id,
                    'plan_name' => $bundle->bundlePackage->name ?? 'basic',
                    'ends_at' => $bundle->expires_at,
                ];

                $token = $jwtService->createSSOToken($client, $app, $subscription);

                $launchUrl = rtrim($app->app_url, '/')
                    . ($app->sso_entrypoint ?? '/auth/sso-login')
                    . "?token=" . $token;

                DB::table('custom_bundle_service_app')
                    ->where('custom_bundle_id', $bundle->id)
                    ->where('service_app_id', $app->id)
                    ->update([
                        'external_profile_url' => $launchUrl,
                        'updated_at' => $now,
                    ]);
            }

            $apps->push([
                'id' => $app->id,
                'name' => $app->name,
                'slug' => $app->slug,
                'type' => $app->type,
                'category' => $app->category,
                'description' => $app->description,
                'is_external' => $app->is_external,
                'price_amount' => $app->price_amount,
                'price_currency' => $app->price_currency,
                'rating_average' => $app->rating_average,
                'rating_scale' => $app->rating_scale,
                'reviews_count' => $app->reviews_count,
                'icon_type' => $app->icon_type,
                'icon_url' => $app->icon_url,
                'icon_alt' => $app->icon_alt,
                'launch_url' => $launchUrl,
                'subscription_expires_at' => $bundle->expires_at,
                'bundle_price' => $bundle->price->amount,
                'bundle_price_currency' => $bundle->price->currency,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS APPS (ADDED – SAME RESPONSE SHAPE)
    |--------------------------------------------------------------------------
    */
// 1️⃣ Fetch active business app subscriptions for the authenticated client
   $businessSubscriptions = BusinessAppSubscription::with(['app', 'plan'])
        ->where('customer_id', $client->id)
        ->where('status', 'active')
        ->get();

    foreach ($businessSubscriptions as $subscription) {
        $app = $subscription->app;
        $plan = $subscription->plan;

        if (!$app || !$plan) continue;

        $launchUrl = $app->app_url ?? null;
        if ($app->is_external && $app->app_url) {
            $token = $jwtService->createSSOToken($client, $app, $subscription);
            $launchUrl = rtrim($app->app_url, '/') . ($app->sso_entrypoint ?? '/auth/sso-login') . "?token=" . $token;
        }

        $apps->push([
            'id' => $app->id,
            'name' => $app->name,
            'slug' => $app->slug,
            'type' => $app->type,
            'category' => $app->category ?? 'Business',
            'description' => $app->description,
            'is_external' => $app->is_external,
            'price_amount' => $plan->price_amount,
            'price_currency' => $plan->price_currency,
            'rating_average' => $app->rating_average ?? 0,
            'rating_scale' => $app->rating_scale ?? 5,
            'reviews_count' => $app->reviews_count ?? 0,
            'icon_type' => $app->icon_type,
            'icon_url' => $app->icon_url,
            'icon_alt' => $app->icon_alt,
            'launch_url' => $launchUrl,
            'subscription_expires_at' => $subscription->expires_at,
            'bundle_price' => $plan->price_amount,
            'bundle_price_currency' => $plan->price_currency,
        ]);
    }


    return response()->json([
        'status' => true,
        'data' => $apps->unique('id')->values()
    ]);
}






    // ----------------- Get Custom Bundle by ID -----------------
    public function showCustomBundle(CustomBundle $bundle)
    {
        $client = auth()->user();
        if ($bundle->customer_id !== $client->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $bundle->load('bundlePackage', 'applications');

        return new CustomBundleDetailResource($bundle);
    }







    // ----------------- Delete Application from Bundle -----------------
    public function destroyApplication(CustomBundle $bundle, int $appId)
    {
        $client = auth()->user();
        if ($bundle->customer_id !== $client->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if (Carbon::now()->greaterThan($bundle->expires_at)) {
            return response()->json(['success' => false, 'message' => 'Modification window has expired.'], 403);
        }

        $deletedCount = $bundle->applications()
            ->newPivotStatement()
            ->where('service_app_id', $appId)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => Carbon::now()]);

        if ($deletedCount > 0) {
            return response()->json([
                'success' => true,
                'message' => 'Application deleted successfully',
                'userId' => $bundle->customer_id,
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'Application not found in bundle'], 404);
    }

    // ----------------- Packages Comparison -----------------
    public function comparison(Request $request)
    {
        $packages = BundlePackage::all();

        $currentBundleId = 2; // Example placeholder
        $comparisonBundles = $packages->map(function ($package) use ($currentBundleId) {
            $package->isCurrent = $package->id === $currentBundleId;
            $package->numberOfApplications = match ($package->id) {
                1 => 3,
                2 => 6,
                3 => 10,
                default => 0,
            };
            $package->supportLevel = match ($package->id) {
                1 => 'Basic',
                2 => 'Priority',
                3 => 'Premium',
                default => 'N/A',
            };
            return $package;
        });

        return new BundleComparisonResource(['bundles' => $comparisonBundles]);
    }

    // ----------------- Launch App (SSO) -----------------
    public function launchApp(ServiceApp $app)
    {
        $client = auth()->user();
        if (!$client) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Find active subscription for this app
        $subscription = DB::table('subscription_apps')
            ->where('client_id', $client->id)
            ->where('app_name', $app->name)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if (!$subscription) {
            return response()->json([
                'status' => false,
                'message' => 'No active subscription found for this app.',
            ], 403);
        }

        // Generate JWT token using JWTService
        $jwtService = app(\App\Services\JWTService::class);
        $token = $jwtService->createSSOToken($client, $app, $subscription);

        $redirectUrl = rtrim($app->app_url, '/') . ($app->sso_entrypoint ?? '/auth/sso-login') . "?token=" . $token;

        return response()->json([
            'status' => true,
            'redirect_url' => $redirectUrl,
        ]);
    }


    // ----------------- SSO Token Validation -----------------
    // This endpoint is for child apps to validate JWT tokens
    public function ssoValidate(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        $tokenString = $request->input('token');

        $jwtService = app(\App\Services\JWTService::class);
        $decoded = $jwtService->verifyToken($tokenString);

        if (!$decoded) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        // Verify the token hasn't expired
        if (isset($decoded->exp) && $decoded->exp < time()) {
            return response()->json(['message' => 'Token has expired.'], 401);
        }

        // Return user info and permissions embedded in token
        return response()->json([
            'status' => true,
            'client_id' => $decoded->sub,
            'email' => $decoded->email,
            'name' => $decoded->name,
            'role' => $decoded->role ?? 'user',
            'permissions' => $decoded->permissions ?? ['read'],
            'app_id' => $decoded->app_id ?? null,
            'subscription_ends' => $decoded->subscription_ends ?? null,
        ]);
    }
    




public function subscribeBundleWithPaypal(StoreCustomBundleRequest $request, PayPalService $paypal)
    {
        Log::info('Reached subscribeBundleWithPaypal');
     $client = auth()->user();
    $validated = $request->validated();

    $package = BundlePackage::findOrFail($validated['bundleId']);

    // 1️⃣ Create pending bundle
    $bundle = CustomBundle::create([
        'customer_id' => $client->id,
        'bundle_package_id' => $package->id,
        'total_price_amount' => $package->price_amount,
        'total_price_currency' => $package->price_currency,
        'status' => 'pending',
        'requested_app_ids' => $validated['applications'] ?? [],
        'expires_at' => now()->addDays(30),
    ]);

    // 2️⃣ Create payment record
    $payment = Payment::create([
        'payable_type' => CustomBundle::class,
        'payable_id' => $bundle->id,
        'payer_id' => $client->id,
        'payer_type' => 'client',
        'provider' => 'paypal',
        'amount' => $package->price_amount,
        'currency' => $package->price_currency,
        'status' => 'pending',
    ]);

    // 3️⃣ Create PayPal order
    $order = $paypal->createOrder(
        $payment->amount,
        $payment->currency,
        route('paypal.success', $payment->id, true),  // full URL
        route('paypal.cancel', $payment->id, true)   // full URL
    );

    Log::info('PayPal Order', $order);

    $approvalLink = collect($order['links'] ?? [])
        ->firstWhere('rel', 'approve')['href'] ?? null;

    if (!$approvalLink) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to create PayPal approval link',
            'order' => $order
        ], 500);
    }

    // 4️⃣ Save PayPal order ID
    $payment->update([
        'provider_payment_id' => $order['id'] ?? null,
        'meta' => $order,
    ]);

    return response()->json([
        'status' => true,
        'approval_url' => $approvalLink,
    ]);
}




}
