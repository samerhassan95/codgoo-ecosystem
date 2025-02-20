<?php

namespace App\Repositories;

use App\Models\Notification;

class NotificationRepository
{
    public function createNotification($notifiable, $title, $message, $token)
    {
        return $notifiable->notifications()->create([
            'title' => $title,
            'message' => $message,
            'token' => $token,
            'notifiable_id' => $notifiable->id,  
            'notifiable_type' => get_class($notifiable), 
        ]);
    }
}
