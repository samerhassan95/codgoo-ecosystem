<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->morphTo('created_by');
    }
    public function attachments()
    {
        return $this->morphMany(attachment::class, 'attachable');
    }

    public function addons()
    {
        return $this->hasMany(ProjectAddons::class);
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class);
    }

    public function getTotalAddonsAmount()
    {
        $projectAddonsTotal = $this->addons()->with('addon')->get()->sum(function ($projectAddon) {
            return $projectAddon->addon->price ?? 0;
        });

        $productAddonsTotal = $this->product ? $this->product->addons()->with('addon')->get()->sum(function ($productAddon) {
            return $productAddon->addon->price ?? 0;
        }) : 0;

        return $projectAddonsTotal + $productAddonsTotal;
    }


    public function sliders()
    {
        return $this->belongsToMany(Slider::class, 'slider_projects')
                    ->withPivot('image')
                    ->withTimestamps();
    }
    



}
