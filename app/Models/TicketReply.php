<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    protected $fillable = [
        'ticket_id',
        'reply',
        'creator_id',
        'creator_type',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ticket
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the creator (polymorphic - can be Admin or Client)
     */
    public function creator()
    {
        return $this->morphTo();
    }

    /**
     * Check if reply is from admin
     */
    public function isFromAdmin()
    {
        return $this->creator_type === Admin::class;
    }

    /**
     * Check if reply is from client
     */
    public function isFromClient()
    {
        return $this->creator_type === Client::class;
    }
}