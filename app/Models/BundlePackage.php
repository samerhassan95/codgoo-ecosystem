<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundlePackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tagline',
        'price_amount',
        'price_currency',
        'features',
        'savings_percentage',
        'savings_text',
        'badges',
    ];

    // Cast JSON columns to arrays/objects
    protected $casts = [
        'features' => 'array',
        'badges' => 'array',
        'price_amount' => 'integer',
        'savings_percentage' => 'integer',
    ];

    /**
     * Relationship: Get the custom bundle orders based on this package.
     */
    public function customBundles()
    {
        return $this->hasMany(CustomBundle::class);
    }
}
