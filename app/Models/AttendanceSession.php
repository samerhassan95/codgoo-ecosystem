<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'ip_address',
        'check_in_time',
        'check_out_time',
        'is_in_office'
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
