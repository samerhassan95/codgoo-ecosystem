<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Addon extends Model
{
    use HasFactory;
    protected $guarded = [];

    
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_addons')
                    ->withPivot(['some_column_in_pivot_if_needed']);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_addons')
                    ->withPivot(['some_column_in_pivot_if_needed']);
    }
    
    
}
