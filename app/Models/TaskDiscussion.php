<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TaskDiscussion extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'message',
        'created_by',
        'created_by_type',
        'status',
    ];

    

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }


    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }
}
