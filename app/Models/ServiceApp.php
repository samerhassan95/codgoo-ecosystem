<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceApp extends Model
{
    use HasFactory;
    protected $table = 'service_apps';



    // Mass assignable fields
    protected $fillable = [
        'name',
        'slug',
        'type',
        'overview', 
        'category',
        'description',       // TEXT
        'price_amount',
        'price_currency',
        'rating_average',
        'rating_scale',
        'reviews_count',
        'icon_type',
        'icon_url',
        'icon_alt',

        // NEW / updated
        'version',
        'size',
        'installs',
        'last_update',
        'has_free_trial',
        'pricing_type',
        'features',          // JSON
        'screenshots',       // JSON
        'integrations',
        'documentation_url',
        'help_center_url',
        'contact_url',
    ];

    // Cast columns to proper types
    protected $casts = [
        'price_amount' => 'integer',
        'rating_average' => 'float',
        'rating_scale' => 'integer',
        'reviews_count' => 'integer',

        // NEW / updated
               // JSON → array
        'screenshots' => 'array',    // JSON → array
        
        'has_free_trial' => 'boolean',
        'last_update' => 'date',
    ];

    /**
     * Relationship: Get the custom bundles this application belongs to.
     */
    public function customBundles()
    {
        return $this->belongsToMany(
            CustomBundle::class,
            'custom_bundle_service_app'
        )->withTimestamps();
        // Note: We don't need ->withTrashed() here unless we want to view removed apps.
    }
}
