<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceApp extends Model
{
    use HasFactory;

    // Mass assignable fields (based on your input/seeding)
    protected $fillable = [
        'name',
        'slug',
        'type',
        'category',
        'description',
        'price_amount',
        'price_currency',
        'rating_average',
        'rating_scale',
        'reviews_count',
        'icon_type',
        'icon_url',
        'icon_alt',
    ];

    // Cast properties to their native types
    protected $casts = [
        'price_amount' => 'integer',
        'rating_average' => 'float',
        'rating_scale' => 'integer',
        'reviews_count' => 'integer',
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
