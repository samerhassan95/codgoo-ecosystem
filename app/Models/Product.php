<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function media() 
    {
        return $this->hasMany(ProductMedia::class);
    }
    
    public function addons()
    {
        return $this->belongsToMany(Addon::class, 'product_addons');
    }

    public function attachments()
    {
        return $this->morphMany(attachment::class, 'attachable');
    }    
}
