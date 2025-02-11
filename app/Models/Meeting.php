<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = ['slot_id', 'client_id', 'start_time', 'end_time', 'jitsi_url', 'meeting_name', 'project_id','status', 'description'];

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
}
