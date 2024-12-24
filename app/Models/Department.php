<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    // Relationship with Tickets
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'department_id');
    }
}
