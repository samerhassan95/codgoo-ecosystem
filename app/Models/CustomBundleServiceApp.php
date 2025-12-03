<?php

// app/Models/CustomBundleServiceApp.php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot; // 💡 CRITICAL: Must use Pivot
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomBundleServiceApp extends Pivot // 💡 FIX IS HERE
{
    use SoftDeletes;

    // The table name is required when using a custom pivot model
    protected $table = 'custom_bundle_service_app';

    // You may need to specify the primary keys if they are not the default 'id'
    protected $primaryKey = ['custom_bundle_id', 'service_app_id'];

    // Make the keys public if you are using composite keys
    public $incrementing = false;
}
