<?php

namespace App\Observers;

use App\Models\RequestedApi;
use App\Models\NotificationTemplate;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class RequestedApiObserver
{
    public function created($model)
    {
        $modelType = class_basename($model);
        Log::info('Created model type: ' . $modelType);

        $title = "New {$modelType} Created";
        $message = "A new {$modelType} request has been submitted.";
dd($model->employee->device_token);
        if ($model->employee && $model->employee->device_token) {
            $payload = [
                'request_type' => strtolower($modelType),
                'id' => (string) $model->id,
                'notification_type' => 'request_status_created',
            ];

            Log::info('Sending notification to tester device', [
            'device_token' => $tester->device_token,
            'title'        => $title,
            'message'      => $message,
            'payload'      => $payload,
            ]);
            try {
                app(\App\Services\FirebaseService::class)->sendNotification(
                    $model->employee->device_token,
                    $title,
                    $message,
                    $payload
                );

                app(\App\Repositories\NotificationRepository::class)->createNotification(
                    $model->employee,
                    $title,
                    $message,
                    $model->employee->device_token,
                    'request_status_created',
                    $payload
                );
            } catch (\Exception $e) {
                Log::error('Error sending request status notification: ' . $e->getMessage());
            }
        }
    }
}
