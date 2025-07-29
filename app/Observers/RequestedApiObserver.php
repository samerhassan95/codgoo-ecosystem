<?php

namespace App\Observers;

use App\Models\RequestedApi;
use App\Models\NotificationTemplate;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class RequestedApiObserver
{
    public function created(RequestedApi $api)
    {
        $tester = $api->screen->task
            ?->assignments()
            ->with('employee') 
            ->get()
            ->firstWhere(fn($assignment) => $assignment->employee?->role === 'back_end')
            ?->employee;
        if ($tester && $tester->device_token) {
            $template = NotificationTemplate::where('type', 'api_requested')->first();

            if (!$template) {
                Log::error('Notification template "api_requested" not found.');
                return;
            }

            $title = $template->title;
            $message = str_replace(
                ['{endpoint}', '{task_name}'],
                [$api->endpoint, $api->screen->task->name],
                $template->message
            );

            $payload = [
                'api_id' => (string) $api->id,
                'notification_type' => 'api_requested',
            ];

            try {
                app(FirebaseService::class)->sendNotification($tester->device_token, $title, $message);
                app(NotificationRepository::class)->createNotification($tester, $title, $message, $tester->device_token, 'api_requested');
            } catch (\Exception $e) {
                Log::error('Error sending api_requested notification: ' . $e->getMessage());
            }
        }
    }
}
