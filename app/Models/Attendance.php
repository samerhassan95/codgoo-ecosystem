<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'total_hours',
        'date',
        'employee_id',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
