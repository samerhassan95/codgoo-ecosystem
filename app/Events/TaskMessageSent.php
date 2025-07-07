<?php

namespace App\Events;

use App\Models\TaskDiscussionMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskMessageSent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

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
            'type' => $this->message->type,
            'message' => $this->message->message,
            'file_url' => $this->message->file_path 
                ? asset('storage/' . $this->message->file_path)
                : null,
            'sender' => [
                'id' => $this->message->sender_id,
                'type' => $this->message->sender_type,
                'name' => optional($this->message->sender)->name ?? 'Unknown',
                'image' => optional($this->message->sender)->image ?? null,
            ],
            'sent_at' => $this->message->created_at->toDateTimeString(),
        ];
    }

}
