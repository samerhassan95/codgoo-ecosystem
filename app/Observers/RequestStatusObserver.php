<?php

namespace App\Observers;

use Illuminate\Support\Facades\Log;
use App\Services\FirebaseService;
use App\Models\NotificationTemplate;

trait RequestStatusObserver
{
    public function updated($model)
    {
        if ($model->isDirty('status') && $model->status !== 'pending') {
            try {
                $employee = $model->employee;
                if ($employee && $employee->device_token) {
                   $template = NotificationTemplate::where('type', 'request_status_updated')->first();

                if (!$template) {
                    Log::error('Notification template "request_status_updated" not found.');
                    return;
                }

                $title = $template->title;
                $body = str_replace(
                    ['{request_type}', '{status}'],
                    [class_basename($model), ucfirst($model->status)],
                    $template->message
                );

                    Log::info('Preparing to send notification to employee', [
                        'employee_id' => $employee->id,
                        'device_token' => $employee->device_token,
                        'title' => $title,
                        'body' => $body,
                        'payload' => $payload,
                    ]);
                    app(FirebaseService::class)->sendNotification($employee->device_token, $title, $body, [
                        'request_id' => $model->id,
                        'notification_type' => 'request_status_updated',
                    ]);

                    Log::info('Status notification sent to employee ID ' . $employee->id);
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send status update notification: " . $e->getMessage());
            }
        }
    }
}
