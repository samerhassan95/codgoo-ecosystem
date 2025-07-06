<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
         'created_by',
        'attendance_id',
        'achievement_description',
        'submitted_at',
        'issues_notes',
        
    ];



    // public function creator()
    // {
    //     return $this->morphTo();
    // }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function attachments()
    {
        return $this->hasMany(AchievementAttachment::class);
    }

    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}
