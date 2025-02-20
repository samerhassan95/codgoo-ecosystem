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
        ]);
    }
}
