<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'reply',
        'admin_id',
    ];

    // Relationship with Ticket
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // Relationship with Admin
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
