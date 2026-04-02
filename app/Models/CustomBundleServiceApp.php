<?php

// app/Models/CustomBundleServiceApp.php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot; // 💡 CRITICAL: Must use Pivot
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomBundleServiceApp extends Pivot // 💡 FIX IS HERE
{
    use SoftDeletes;

    protected $table = 'custom_bundle_service_app';

    // 🎯 CRITICAL FIX 1: Define the composite keys from your migration
    protected $primaryKey = ['custom_bundle_id', 'service_app_id'];

    // 🎯 CRITICAL FIX 2: Since the primary key is composite, disable auto-increment
    public $incrementing = false;

    protected $fillable = [
        'custom_bundle_id',
        'service_app_id',
        'external_profile_url', // Must be fillable for updates
    ];
}
