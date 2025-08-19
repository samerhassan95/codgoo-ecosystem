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
        $frontend = $screen->task
            ?->assignments()
            ->with('employee')
            ->get()
            ->firstWhere(fn($assignment) => $assignment->employee?->role === 'front_end')
            ?->employee;

        if (
            $screen->dev_mode &&
            $frontend &&
            $frontend->device_token
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

            try {
                $dataPayload = [
                    'screen_id' => $screen->id,
                    'notification_type' => 'screen_created',
                ];

                app(FirebaseService::class)->sendNotification($frontend->device_token, $title, $message);
                app(NotificationRepository::class)->createNotification(
                    $frontend,
                    $title,
                    $message,
                    $frontend->device_token,
                    'screen_created'
                );
            } catch (\Exception $e) {
                Log::error('Error sending screen_created notification to frontend: ' . $e->getMessage());
            }
        }

        // tester
        $tester = $screen->task
            ?->assignments()
            ->with('employee')
            ->get()
            ->firstWhere(fn($assignment) => $assignment->employee?->role === 'tester')
            ?->employee;

        if ($tester && $tester->device_token) {
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

            try {
                $dataPayload = [
                    'screen_id' => $screen->id,
                    'notification_type' => 'screen_created',
                ];

                app(FirebaseService::class)->sendNotification($tester->device_token, $title, $message);
                app(NotificationRepository::class)->createNotification(
                    $tester,
                    $title,
                    $message,
                    $tester->device_token,
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

        $changedFields = array_diff_assoc($screen->getAttributes(), $original);

        unset($changedFields['updated_at'], $changedFields['created_at']);

       if (!empty($changedFields)) {
            $filteredChanges = array_diff_key(
                $changedFields,
                array_flip(['implemented', 'integrated', 'dev_mode'])
            );

            if (!empty($filteredChanges)) {
                $this->sendEditNotification($screen, $filteredChanges);
            }
        }

        if (!$original['implemented'] && $screen->implemented) {
            $this->sendTesterNotification($screen, 'screen_implemented', 'Screen implemented');
        }

        if (!$original['integrated'] && $screen->integrated) {
            $this->sendTesterNotification($screen, 'screen_integrated', 'Screen integrated');
        }

        if (!$original['dev_mode'] && $screen->dev_mode) {
            $this->sendDevModeNotification($screen, 'screen_dev_mode_enabled', 'Screen entered dev mode');
        }
    }

    private function sendEditNotification(Screen $screen, array $changedFields)
    {
        $title = "Screen Updated";
        $message = "The screen '{$screen->name}' in task '{$screen->task->name}' was updated.";

        $frontend = $screen->task
            ?->assignments()
            ->with('employee')
            ->get()
            ->firstWhere(fn($assignment) => $assignment->employee?->role === 'front_end')
            ?->employee;

        if ($frontend && $frontend->device_token) {
            app(FirebaseService::class)->sendNotification($frontend->device_token, $title, $message, null, [
                'task_id' => $screen->task_id,
                'notification_type' => 'screen_updated',
            ]);

            app(NotificationRepository::class)->createNotification(
                $frontend,
                $title,
                $message,
                $frontend->device_token,
                'screen_updated'
            );
        }

        $tester = $screen->task
            ?->assignments()
            ->with('employee')
            ->get()
            ->firstWhere(fn($assignment) => $assignment->employee?->role === 'tester')
            ?->employee;

        if ($tester && $tester->device_token) {
            app(FirebaseService::class)->sendNotification($tester->device_token, $title, $message, null, [
                'task_id' => $screen->task_id,
                'notification_type' => 'screen_updated',
            ]);

            app(NotificationRepository::class)->createNotification(
                $tester,
                $title,
                $message,
                $tester->device_token,
                'screen_updated'
            );
        }
    }



    private function sendTesterNotification(Screen $screen, string $templateType, string $defaultTitle)
    {
        if (!$screen->task) {
            return;
        }

        $tester = $screen->task
            ?->assignments()
            ->with('employee')
            ->get()
            ->firstWhere(fn($assignment) => $assignment->employee?->role === 'tester')
            ?->employee;

        if (!$tester || !$tester->device_token) {
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

        $deviceToken = $tester->device_token;

        try {
            $dataPayload = [
                'task_id' => $screen->task_id,
                'notification_type' => $templateType,
            ];

            app(FirebaseService::class)->sendNotification($deviceToken, $title, $message);
            app(NotificationRepository::class)->createNotification(
                $tester,
                $title,
                $message,
                $deviceToken,
                $templateType
            );
        } catch (\Exception $e) {
            Log::error("Error sending $templateType notification: " . $e->getMessage());
        }
    }

    private function sendDevModeNotification(Screen $screen, string $templateType, string $defaultTitle)
    {
        if (!$screen->task) {
            return;
        }

        $recipients = $screen->task
            ?->assignments()
            ->with('employee')
            ->get()
            ->filter(fn($assignment) => in_array($assignment->employee?->role, ['front_end', 'ui_ux']))
            ->pluck('employee');

        if ($recipients->isEmpty()) {
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

        foreach ($recipients as $user) {
            if (!$user->device_token) {
                continue;
            }

            try {

                app(FirebaseService::class)->sendNotification($user->device_token, $title, $message, null, [
                    'task_id' => $screen->task_id,
                    'notification_type' => $templateType,
                ]);
                app(NotificationRepository::class)->createNotification(
                    $user,
                    $title,
                    $message,
                    $user->device_token,
                    $templateType,
                    
                    [
                        'screen_id' => $screen->id,
                        'notification_type' => $templateType,
                    ]
                );
            } catch (\Exception $e) {
                Log::error("Error sending $templateType notification to user {$user->id}: " . $e->getMessage());
            }
        }
    }

}
