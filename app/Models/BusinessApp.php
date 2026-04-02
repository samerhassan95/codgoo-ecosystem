<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessApp extends Model
{
       protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active'
    ];

    public function plans()
    {
        return $this->hasMany(BusinessAppPlan::class);
    }
}
