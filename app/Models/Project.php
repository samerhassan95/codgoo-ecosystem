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

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
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


    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function getTotalAddonsAmount()
    {
        $projectAddonsTotal = $this->addons()->sum('price');

        $productAddonsTotal = $this->product
            ? $this->product->addons()->sum('price')
            : 0;

        return $projectAddonsTotal + $productAddonsTotal;
    }


    public function updateProjectStatusIfNeeded()
    {
        if ($this->milestones->every(fn($milestone) => $milestone->status === 'completed')) {
            $this->update(['status' => 'completed']);
        }
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

}
