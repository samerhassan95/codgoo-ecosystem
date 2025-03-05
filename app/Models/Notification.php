<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'message', 'token', 'is_read','notifiable_id','notifiable_type'];

    protected $casts = [
        'data' => 'array', 
    ];    
    public function notifiable()
    {
        return $this->morphTo();
    }
}
