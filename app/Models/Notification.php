<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'message', 'token', 'is_read','notifiable_id','notifiable_type','data','notification_template_id'];

    protected $casts = [
        'data' => 'array', 
    ];    
    public function notifiable()
    {
        return $this->morphTo();
    }

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

}
