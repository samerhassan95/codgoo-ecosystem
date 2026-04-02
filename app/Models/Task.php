<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    
    protected $guarded = [];

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }
    public function project()
{
    return $this->belongsTo(Project::class);
}

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }
    public function assignments()
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function screens()
    {
        return $this->hasMany(Screen::class);
    }

    public function devModeScreens()
    {
        return $this->hasMany(Screen::class)->where('dev_mode', 1);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'task_assignments')
                    ->withPivot(['status', 'estimated_hours', 'header'])
                    ->withTimestamps();
    }


    
}
