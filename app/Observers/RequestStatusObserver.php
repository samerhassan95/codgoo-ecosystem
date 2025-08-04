<?php

namespace App\Observers;

use Illuminate\Support\Facades\Log;
use App\Services\FirebaseService;

trait RequestStatusObserver
{
    public function updated($model)
    {
        if ($model->isDirty('status') && $model->status !== 'pending') {
            try {
                $employee = $model->employee;
                if ($employee && $employee->device_token) {
                    $title = "Your request has been " . $model->status;
                    $body = "Request type: " . class_basename($model) . " | Status: " . ucfirst($model->status);
                    
                    app(FirebaseService::class)->sendNotification($employee->device_token, $title, $body);

                    Log::info('Status notification sent to employee ID ' . $employee->id);
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send status update notification: " . $e->getMessage());
            }
        }
    }
}
