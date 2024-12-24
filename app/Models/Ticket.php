<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department_id',
        'priority',
        'description',
        'created_by',
        'status',
        'attachment',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'created_by');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class)->withDefault();
    }
}
