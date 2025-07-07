<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskDiscussionMessage extends Model
{
  
    protected $fillable = ['task_id', 'sender_id', 'sender_type', 'message', 'type', 'file_path'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function sender()
    {
        return $this->morphTo();
    }
}
