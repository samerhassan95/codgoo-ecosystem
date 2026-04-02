<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = ['slot_id', 'start_time', 'end_time', 'jitsi_url', 'meeting_name', 'project_id','status', 'description','client_id','task_id','date','notes'];
        protected $casts = [
        'date' => 'date', // ✅ optional but recommended
    ];

    public function slot()
    {
        return $this->belongsTo(AvailableSlot::class, 'slot_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
    public function logs()
    {
        return $this->hasMany(MeetingLog::class);
    }
public function employees()
{
    return $this->belongsToMany(Employee::class, 'meeting_employees', 'meeting_id', 'employee_id');
}


    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
