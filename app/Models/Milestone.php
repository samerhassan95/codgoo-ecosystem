<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Milestone extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function setEndDateAttribute($value)
    {
        if ($this->attributes['start_date'] && $this->attributes['period']) {
            $this->attributes['end_date'] = Carbon::parse($this->attributes['start_date'])
                ->addDays($this->attributes['period'])->toDateString();
        }
    }
}
