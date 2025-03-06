<?php

namespace App\Repositories;

use App\Models\Notification;

class NotificationRepository
{
    public function createNotification($notifiable, $title, $message, $deviceToken, $notificationType)
    {
        $template = NotificationTemplate::where('type', $notificationType)->first();
    
        if (!$template) {
            \Log::error('Notification template not found for type: ' . $notificationType);
            return null;
        }
    
        return Notification::create([
            'notifiable_id' => $notifiable->id,
            'notifiable_type' => get_class($notifiable),
            'title' => $title,
            'message' => $message,
            'data' => json_encode(['device_token' => $deviceToken]),
            'is_read' => false,
            'notification_template_id' => $template->id,
        ]);
    }
    

}
