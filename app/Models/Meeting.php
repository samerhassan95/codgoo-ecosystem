<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = ['slot_id', 'client_id', 'start_time', 'end_time', 'jitsi_url'];

    public function slot()
    {
        return $this->belongsTo(AvailableSlot::class, 'slot_id');
    }
}
