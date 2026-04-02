<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskDiscussionMessage extends Model
{
  
    protected $fillable = ['task_id', 'sender_id', 'sender_type', 'message', 'type', 'file_path','discussion_id',];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function sender()
    {
        return $this->morphTo();
    }
    
        public function discussion()
    {
        return $this->belongsTo(TaskDiscussion::class, 'discussion_id');
    }
}
