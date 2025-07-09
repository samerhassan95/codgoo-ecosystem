<?php

namespace App\Events;

use App\Models\TaskDiscussionMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(TaskDiscussionMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('task.discussion.' . $this->message->task_id);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->message->id,
            'task_id' => $this->message->task_id,
            'message' => $this->message->message,
            'type' => $this->message->type,
            'file_url' => $this->message->file_path 
                ? asset('storage/' . $this->message->file_path) 
                : null,
            'sender' => [
                'id' => $this->message->sender_id,
                'type' => class_basename($this->message->sender_type),
                'name' => optional($this->message->sender)->name ?? 'Unknown',
            ],
            'created_at' => $this->message->created_at->toDateTimeString(),
        ];
    }
}