<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Task;


class TaskDiscussion extends Model
{
    use HasFactory;

    protected $fillable = [
    'task_id',
    'message',
    'creator_id',
    'creator_type',
    'status',
    ];

    

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

public function members()
{
    return $this->morphedByMany(
        Employee::class, // Replace with your main user type
        'user',
        'task_discussion_members',
        'discussion_id',
        'user_id'
    )
    ->withPivot('user_type')
    ->withTimestamps();
}

    public function createdBy(): MorphTo
    {
        return $this->morphTo('createdBy', 'creator_type', 'creator_id');
    }
    
        public function task()
    {
        return $this->belongsTo(Task::class);
    }
    
    public function messages()
{
    return $this->hasMany(TaskDiscussionMessage::class, 'discussion_id');
}
}
