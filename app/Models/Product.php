<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
    'name',
    'description',
    'price',
    'note',
    'image',
    'category_id',
    'background_image',
    'type',
];
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
        return $this->morphMany(Attachment::class, 'attachable');
    }    

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
    public function sliders()
{
    // Adjust the class name if your model is named differently (e.g., ProductSlider)
    return $this->hasMany(Slider::class); 
}
}
