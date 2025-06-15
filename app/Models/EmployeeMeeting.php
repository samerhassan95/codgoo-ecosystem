<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_type',
        'created_by_id',
        'title',
        'description',
        'visibility',
        'meeting_url',
        'start_time',
        'end_time',
        'date',
        'status',
        'zoom_meeting_id',
        'zoom_meeting_passcode',
    ];

 
    public function participants()
    {
        return $this->belongsToMany(Employee::class, 'meeting_participants');
    }

    public function creator()
    {
        return $this->morphTo();
    }
}
