<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
         'created_by',
        'attendance_id',
        'achievement_description',
        'submitted_at',
        'achievement_type',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // public function creator()
    // {
    //     return $this->morphTo();
    // }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function achievementType()
    {
        return $this->belongsTo(AchievementType::class);
    }
}
