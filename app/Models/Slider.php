<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
protected $fillable = ['product_id', 'image'];

    protected $casts = [
        'image' => 'array', // This converts the array to JSON for the DB automatically
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
