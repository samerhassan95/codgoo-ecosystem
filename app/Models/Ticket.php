<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    // use SoftDeletes;

    protected $fillable = [
        'subject',
        'message',
        'department_id',
        'priority',
        'status',
        'created_by',
        'attachments',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Don't cast attachments here - handle in resource

    /**
     * Get the client who created the ticket
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'created_by');
    }

    /**
     * Get the department
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get all replies
     */
    public function replies()
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest reply
     */
    public function latestReply()
    {
        return $this->hasOne(TicketReply::class)->latest();
    }

    /**
     * Get attachments as array
     */
    public function getAttachmentsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        
        // If already an array, return it
        if (is_array($value)) {
            return $value;
        }
        
        // Try to decode JSON
        $decoded = json_decode($value, true);
        return $decoded ?? [];
    }

    /**
     * Set attachments
     */
    public function setAttachmentsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['attachments'] = json_encode($value);
        } else {
            $this->attributes['attachments'] = $value;
        }
    }
}