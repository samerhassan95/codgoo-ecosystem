<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingLog extends Model
{

    protected $fillable = [
        'meeting_id',
        'user_id',  // ✅ Add this
        'action',
        'details',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

}
