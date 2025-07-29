<?php

namespace App\Observers;

use App\Models\ImplementedApi;
use App\Models\NotificationTemplate;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class ImplementedApiObserver
{
    public function created(ImplementedApi $implementedApi)
    {
        $tester = $api->screen->task
                    ?->assignments()
                    ->with('employee') 
                    ->get()
                    ->firstWhere(fn($assignment) => $assignment->employee?->role === 'tester')
                    ?->employee;
        if ($tester && $tester->device_token) {
            $template = NotificationTemplate::where('type', 'api_implemented')->first();

            if (!$template) {
                Log::error('Notification template "api_implemented" not found.');
                return;
            }

            $title = $template->title;
            $message = str_replace(
                ['{endpoint}', '{task_name}'],
                [$implementedApi->requestedApi->endpoint, $implementedApi->requestedApi->screen->task->name],
                $template->message
            );

            $payload = [
                'implemented_api_id' => $implementedApi->id,
                'notification_type' => 'api_implemented',
            ];

            try {
                app(FirebaseService::class)->sendNotification($tester->device_token, $title, $message);
                app(NotificationRepository::class)->createNotification($tester, $title, $message, $tester->device_token, 'api_implemented');
            } catch (\Exception $e) {
                Log::error('Error sending api_implemented notification: ' . $e->getMessage());
            }
        }
    }

    public function updated(ImplementedApi $implementedApi)
    {
        if ($implementedApi->isDirty('status') && $implementedApi->status === 'tested') {
            $frontend = $api->screen->task
                        ?->assignments()
                        ->with('employee') 
                        ->get()
                        ->firstWhere(fn($assignment) => $assignment->employee?->role === 'front_end')
            ?->employee;
            if ($frontend && $frontend->device_token) {
                $template = NotificationTemplate::where('type', 'api_tested')->first();

                if (!$template) {
                    Log::error('Notification template "api_tested" not found.');
                    return;
                }

                $title = $template->title;
                $message = str_replace(
                    ['{endpoint}', '{task_name}'],
                    [$implementedApi->requestedApi->endpoint, $implementedApi->requestedApi->screen->task->name],
                    $template->message
                );

                $payload = [
                    'implemented_api_id' => $implementedApi->id,
                    'notification_type' => 'api_tested',
                ];

                try {
                    app(FirebaseService::class)->sendNotification($frontend->device_token, $title, $message);
                    app(NotificationRepository::class)->createNotification($frontend, $title, $message, $frontend->device_token, 'api_tested');
                } catch (\Exception $e) {
                    Log::error('Error sending api_tested notification: ' . $e->getMessage());
                }
            }
        }
    }
}
