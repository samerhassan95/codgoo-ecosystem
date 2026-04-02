<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // 💡 Import for clarity

class CustomBundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'bundle_package_id',
        'bundle_price_id',
        'status',
        'purchased_at',
        'attachment_url',
        'requested_app_ids',
    ];

    protected $casts = [
        'total_price_amount' => 'integer',
        'purchased_at' => 'datetime',
        'requested_app_ids'=>'array',
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
        // NOTE: Adjust the model reference if your customer model is named Client, not User.
        return $this->belongsTo(Client::class);
    }


public function getExpiresAtAttribute()
{
    // Dynamically calculate expiration from purchased_at + price duration
    if ($this->price && $this->purchased_at) {
        return $this->purchased_at->copy()->addDays($this->price->duration_days);
    }
    return null;
}

public function getIsActiveAttribute()
{
    $expiresAt = $this->expires_at;
    return $this->status === 'active' && $expiresAt && now()->lt($expiresAt);
}
    /**
     * Relationship: Get all Service Apps currently included in this custom bundle.
     * * 🟢 MODIFICATION: Added withPivot() to retrieve the deep link URL.
     */
public function applicationsPivot(): BelongsToMany
{
    return $this->belongsToMany(
        ServiceApp::class,
        'custom_bundle_service_app',
        'custom_bundle_id',
        'service_app_id'
    )
    ->withTimestamps()
    ->withPivot(['external_profile_url', 'deleted_at']);
}

// Used for reading apps (filters out soft-deleted pivots)
public function applications(): BelongsToMany
{
    return $this->applicationsPivot()->wherePivotNull('deleted_at');
}

    /**
     * Relationship: Get all Service Apps that were ever part of this custom bundle.
     */
    public function allApplications(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceApp::class,
            'custom_bundle_service_app'
        )
            ->withTimestamps()
            ->withPivot([
                'external_profile_url', // 🔑 REQUIRED: Fetch the deep link URL
            ])
            ->using(CustomBundleServiceApp::class)
            ->withTrashed();
    }
    
    public function payment()
{
    return $this->morphOne(Payment::class, 'payable');
}
public function price()
{
    return $this->belongsTo(BundlePackagePrice::class, 'bundle_price_id');
}
}
