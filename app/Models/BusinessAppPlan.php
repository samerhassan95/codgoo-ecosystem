<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessAppPlan extends Model
{
       protected $fillable = [
        'service_app_id',
        'name',
        'price_amount',
        'price_currency',
        'duration_days',
        'is_active'
    ];

    public function app()
    {
        return $this->belongsTo(ServiceApp::class);
    }
}
