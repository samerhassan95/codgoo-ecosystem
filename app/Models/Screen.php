<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Screen extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'task_id',
        'dev_mode',
        'implemented',
        'integrated',
        'screen_code',
        'estimated_hours',
        'comment'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function reviews()
    {
        return $this->hasMany(ScreenReview::class);
    }

    public function requestedApis()
    {
        return $this->hasMany(RequestedApi::class);
    }

}
