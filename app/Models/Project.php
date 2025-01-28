<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Admin;
use App\Models\Client;
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
        return $this->morphTo();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }


    public function attachments()
    {
        return $this->morphMany(attachment::class, 'attachable');
    }

    public function addons()
{
    return $this->belongsToMany(Addon::class, 'project_addons');
}


    public function milestones()
    {
        return $this->hasMany(Milestone::class);
    }

    public function sliders()
    {
        return $this->belongsToMany(Slider::class, 'slider_projects')
                    ->withPivot('image')
                    ->withTimestamps();
    }

    // // Add a computed property for project status
    // public function getStatusAttribute()
    // {
    //     // Check if there are milestones
    //     if ($this->milestones()->exists()) {
    //         // Check milestone statuses
    //         $milestoneStatuses = $this->milestones()->pluck('status');

    //         if ($milestoneStatuses->contains('in_progress') || $milestoneStatuses->contains('not_started')) {
    //             return 'ongoing';
    //         }

    //         if ($milestoneStatuses->every(fn ($status) => $status === 'completed')) {
    //             return 'completed';
    //         }
    //     }

    //     // Default status if no milestones exist
    //     return 'not_started';
    // }



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
}
