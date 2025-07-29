<?php

namespace App\Observers;

use App\Models\Screen;
use App\Models\NotificationTemplate;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class ScreenObserver
{
    public function created(Screen $screen)
    {
        // Notify frontend developer
        if (
            $screen->dev_mode &&
            $screen->task &&
            $screen->task->frontend_developer &&
            $screen->task->frontend_developer->device_token
        ) {
            $template = NotificationTemplate::where('type', 'screen_created')->first();

            if (!$template) {
                Log::error('Notification template "screen_created" not found.');
                return;
            }

            $title = $template->title;
            $message = str_replace(
                ['{screen_name}', '{task_name}'],
                [$screen->name, $screen->task->name],
                $template->message
            );

            $deviceToken = $screen->task->frontend_developer->device_token;

            try {
                $dataPayload = [
                    'screen_id' => $screen->id,
                    'notification_type' => 'screen_created',
                ];

                app(FirebaseService::class)->sendNotification($deviceToken, $title, $message, $dataPayload);
                app(NotificationRepository::class)->createNotification(
                    $screen->task->frontend_developer,
                    $title,
                    $message,
                    $deviceToken,
                    'screen_created'
                );
            } catch (\Exception $e) {
                Log::error('Error sending screen_created notification to frontend: ' . $e->getMessage());
            }
        }

        // Notify tester
        if (
            $screen->task &&
            $screen->task->tester &&
            $screen->task->tester->device_token
        ) {
            $template = NotificationTemplate::where('type', 'screen_created')->first();

            if (!$template) {
                Log::error('Notification template "screen_created" not found for tester.');
                return;
            }

            $title = $template->title;
            $message = str_replace(
                ['{screen_name}', '{task_name}'],
                [$screen->name, $screen->task->name],
                $template->message
            );

            $deviceToken = $screen->task->tester->device_token;

            try {
                $dataPayload = [
                    'screen_id' => $screen->id,
                    'notification_type' => 'screen_created',
                ];

                app(FirebaseService::class)->sendNotification($deviceToken, $title, $message, $dataPayload);
                app(NotificationRepository::class)->createNotification(
                    $screen->task->tester,
                    $title,
                    $message,
                    $deviceToken,
                    'screen_created'
                );
            } catch (\Exception $e) {
                Log::error('Error sending screen_created notification to tester: ' . $e->getMessage());
            }
        }
    }

    public function updated(Screen $screen)
    {
        $original = $screen->getOriginal();

        if (!$original['implemented'] && $screen->implemented) {
            $this->sendTesterNotification($screen, 'screen_implemented', 'Screen implemented');
        }

        if (!$original['integrated'] && $screen->integrated) {
            $this->sendTesterNotification($screen, 'screen_integrated', 'Screen integrated');
        }
    }

    private function sendTesterNotification(Screen $screen, string $templateType, string $defaultTitle)
    {
        if (!$screen->task || !$screen->task->tester || !$screen->task->tester->device_token) {
            return;
        }

        $template = NotificationTemplate::where('type', $templateType)->first();

        if (!$template) {
            Log::error("Notification template \"$templateType\" not found.");
            return;
        }

        $title = $template->title ?? $defaultTitle;
        $message = str_replace(
            ['{screen_name}', '{task_name}'],
            [$screen->name, $screen->task->name],
            $template->message
        );

        $deviceToken = $screen->task->tester->device_token;

        try {
            $dataPayload = [
                'screen_id' => $screen->id,
                'notification_type' => $templateType,
            ];

            app(FirebaseService::class)->sendNotification($deviceToken, $title, $message, $dataPayload);
            app(NotificationRepository::class)->createNotification(
                $screen->task->tester,
                $title,
                $message,
                $deviceToken,
                $templateType
            );
        } catch (\Exception $e) {
            Log::error("Error sending $templateType notification: " . $e->getMessage());
        }
    }
}
