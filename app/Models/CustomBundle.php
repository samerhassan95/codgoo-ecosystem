<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomBundle extends Model
{
    use HasFactory;

    // CustomBundles can't be soft deleted, but its pivot entries can.
    protected $fillable = [
        'customer_id',
        'bundle_package_id',
        'total_price_amount',
        'total_price_currency',
        'status',
        'purchased_at',
        'expires_at',
    ];

    protected $casts = [
        'total_price_amount' => 'integer',
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Relationship: Get the base package details for this bundle.
     */
    public function bundlePackage()
    {
        return $this->belongsTo(BundlePackage::class);
    }

    /**
     * Relationship: Get the customer who purchased this bundle.
     */
    public function customer()
    {
        // Assuming your user model is App\Models\User
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Get all Service Apps included in this custom bundle.
     */
    public function applications()
    {
        return $this->belongsToMany(
            ServiceApp::class,
            'custom_bundle_service_app'
        )
            ->withTimestamps()
            ->using(CustomBundleServiceApp::class) // 💡 This tells Eloquent to use your custom class
            ->wherePivotNull('deleted_at');
    }

    /**
     * Relationship: Get all Service Apps that were ever part of this custom bundle.
     * This is useful for history and the DELETE endpoint.
     */
    public function allApplications()
    {
        return $this->belongsToMany(
            ServiceApp::class,
            'custom_bundle_service_app'
        )
            ->withTimestamps()
            ->using(CustomBundleServiceApp::class)
            ->withTrashed(); // Include soft-deleted (removed) apps
    }
}
