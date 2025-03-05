<?php

namespace App\Repositories;

use App\Models\Notification;

class NotificationRepository
{
    public function createNotification($notifiable, $title, $message, $token, $data = [])
{
    return Notification::create([
        'notifiable_id' => $notifiable->id,
        'notifiable_type' => get_class($notifiable),
        'title' => $title,
        'message' => $message,
        'token' => $token,
        'data' => !empty($data) ? json_encode($data) : json_encode([]),
        'is_read' => false,
    ]);
}

}
