<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;

class NotificationRepository
{
    public function createNotification($notifiable, $title, $message, $deviceToken, $notificationType, $extraData = [])
{
    if (is_array($notificationType)) {
        $template = NotificationTemplate::where('type', 'chat_message')->first();
        $type = 'chat_message';
        $data = array_merge(['device_token' => $deviceToken], $notificationType);
    } else {
        $template = NotificationTemplate::where('type', $notificationType)->first();
        $type = $notificationType;
        $data = ['device_token' => $deviceToken];
    }

    if (!$template) {
        return null;
    }

    return Notification::create([
        'notifiable_id' => $notifiable->id,
        'notifiable_type' => get_class($notifiable),
        'title' => $title,
        'message' => $message,
        'data' => json_encode($data),
        'is_read' => false,
        'notification_template_id' => $template->id,
    ]);
}



}
