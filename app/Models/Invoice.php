<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'milestone_id',
        'project_id',
        'status',
        'payment_method',
        'payment_proof',
        'due_date',
        'amount',
        'reference',
        'order_no'
    ];

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
