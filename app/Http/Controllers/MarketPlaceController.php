<?php

// app/Http/Controllers/Marketplace/MarketplaceController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BundlePackage;
use App\Models\CustomBundle;
use App\Http\Resources\BundlePackageResource;
use App\Http\Resources\CustomBundleResource;
use App\Http\Resources\CustomBundleDetailResource;
use App\Http\Resources\BundleComparisonResource;
use App\Http\Requests\StoreCustomBundleRequest;
use App\Http\Requests\UpdateCustomBundleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarketplaceController extends Controller
{
    // --- Endpoints 2 & 3: Package Listing and Details ---

    /**
     * Endpoint 2: GET /api/Marketplace/packages
     */
    public function indexPackages()
    {
        $packages = BundlePackage::all();
        return BundlePackageResource::collection($packages);
    }

    /**
     * Endpoint 3: GET /api/Marketplace/packages/{id}
     */
    public function showPackage(BundlePackage $package)
    {
        return new BundlePackageResource($package);
    }

    // --- Endpoint 4: Build Custom Bundle ---

    /**
     * Endpoint 4: POST /api/Marketplace/BuildBundle
     */
    public function storeCustomBundle(StoreCustomBundleRequest $request)
    {
        // 1. Get validated data (bundleId and application IDs)
        $validated = $request->validated();

        // 2. Fetch the base package details
        $package = BundlePackage::findOrFail($validated['bundleId']);

        // 3. Define the expiry time (e.g., 72 hours modification window)
        $expiresAt = Carbon::now()->addHours(72);

        // 4. Create the Custom Bundle record
        $customBundle = CustomBundle::create([
            'customer_id' => $validated['customer']['id'],
            'bundle_package_id' => $validated['bundleId'],
            'total_price_amount' => $package->price_amount, // Use the package price
            'total_price_currency' => $package->price_currency,
            'status' => 'active', // Assuming immediate activation upon purchase/creation
            'purchased_at' => Carbon::now(),
            'expires_at' => $expiresAt,
        ]);

        // 5. Attach the chosen applications to the pivot table
        $customBundle->applications()->attach($validated['applications']);

        // 6. Return the 201 Created response
        return response()->json([
            'data' => new CustomBundleResource($customBundle),
        ], 201);
    }

    // --- Endpoint 5: Get Bundle by ID ---

    /**
     * Endpoint 5: GET /api/Marketplace/Bundle/{id}
     */
    public function showCustomBundle(CustomBundle $bundle)
    {
        // Eager load relationships needed for the detail resource
        $bundle->load('bundlePackage', 'applications');

        // Note: Authorization (checking if the current user owns this bundle)
        // should ideally happen here or in a middleware.

        return new CustomBundleDetailResource($bundle);
    }

    // --- Endpoint 6: Remove Application from Bundle ---

    /**
     * Endpoint 6: DELETE /api/bundle/{bundleId}/applications/{appId}
     * Note: {bundleId} is bound to CustomBundle model via route model binding.
     */
    public function destroyApplication(CustomBundle $bundle, int $appId)
    {
        // Check modification permission (Authorization check)
        if (Carbon::now()->greaterThan($bundle->expires_at)) {
            return response()->json(['success' => false, 'message' => 'Modification window has expired.'], 403);
        }

        // Use the pivot model SoftDeletes to mark the app as removed
        $deletedCount = $bundle->applications()
            ->newPivotStatement()
            ->where('service_app_id', $appId)
            ->whereNull('deleted_at') // Only target apps that aren't already removed
            ->update(['deleted_at' => Carbon::now()]);

        if ($deletedCount > 0) {
            // Optional: Recalculate bundle price/status here if needed

            return response()->json([
                'success' => true,
                'message' => 'Application deleted successfully',
                // Assuming you can get the user ID from the request or bundle model
                'userId' => $bundle->customer_id,
            ], 200);
        }

        // If no rows were affected, the application was either not in the bundle or already removed.
        return response()->json(['success' => false, 'message' => 'Application not found in bundle'], 404);
    }

    // --- Endpoint 7: Upgrade Your Custom Bundle ---

    /**
     * Endpoint 7: PATCH /api/Marketplace/Bundle/{id}
     */
    public function updateCustomBundle(UpdateCustomBundleRequest $request, CustomBundle $bundle)
    {
        // Check modification permission (Authorization check)
        if (Carbon::now()->greaterThan($bundle->expires_at)) {
            return response()->json(['success' => false, 'message' => 'Modification window has expired.'], 403);
        }

        $validated = $request->validated();

        // 1. Update the base bundle information
        $newPackage = BundlePackage::findOrFail($validated['bundleId']);
        $bundle->update([
            'bundle_package_id' => $validated['bundleId'],
            'total_price_amount' => $newPackage->price_amount,
            'total_price_currency' => $newPackage->price_currency,
            // Optionally, update expires_at if the upgrade resets the window
            // 'expires_at' => Carbon::now()->addHours(72),
        ]);

        // 2. Sync the applications
        // sync() handles adding new applications and soft-deleting old ones if needed
        $bundle->applications()->syncWithPivotValues(
            $validated['applications'],
            ['deleted_at' => null], // Ensure new or existing apps are NOT soft-deleted
            true // Use true to soft-delete apps that are removed from the array
        );

        // Reload relationships for the response
        $bundle->load('bundlePackage', 'applications');

        return new CustomBundleDetailResource($bundle);
    }

    // --- Endpoint 8: Get Packages Comparison ---

    /**
     * Endpoint 8: GET /api/Marketplace/packagesComparison
     */
    public function comparison(Request $request)
    {
        $packages = BundlePackage::all();

        // Determine the current user's active bundle ID (placeholder logic)
        // In a real app, this would query the DB using the authenticated user ID.
        $currentBundleId = 2; // Hardcoding 'Professional Bundle' for example

        // Process packages to add derived fields (numberOfApplications, isCurrent, supportLevel)
        // This is done here because the comparison resource needs data not directly on the model.
        $comparisonBundles = $packages->map(function ($package) use ($currentBundleId) {
            // Placeholder: Assume these values are based on convention or another table
            $package->isCurrent = $package->id === $currentBundleId;
            $package->numberOfApplications = match ($package->id) {
                1 => 3, // Starter
                2 => 6, // Professional
                3 => 10, // Enterprise
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

        // Pass the processed collection to the wrapper resource
        return new BundleComparisonResource(['bundles' => $comparisonBundles]);
    }
}
