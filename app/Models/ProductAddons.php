<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductAddons extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'product_addons';

    /**
     * Define the relationship with the Product model.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Define the relationship with the Addon model.
     */
    public function addon()
    {
        return $this->belongsTo(Addon::class);
    }

}
