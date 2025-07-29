<?php

namespace App\Observers;

use App\Models\ScreenReview;
use App\Models\NotificationTemplate;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class ScreenReviewObserver
{
    public function created(ScreenReview $review)
    {
        $screen = $review->screen;
        $task = $screen->task;

        $roleMap = [
            'frontend' => 'frontend_developer',
            'backend' => 'backend_developer',
            'mobile'  => 'mobile_developer',
        ];

        $devAttr = $roleMap[$review->review_type] ?? null;
        $developer = $devAttr ? $task?->$devAttr : null;

        if ($developer && $developer->device_token) {
            $template = NotificationTemplate::where('type', 'screen_review')->first();

            if (!$template) {
                Log::error('Notification template "screen_review" not found.');
                return;
            }

            $title = $template->title;
            $message = str_replace(
                ['{review_type}', '{screen_name}', '{task_name}'],
                [ucfirst($review->review_type), $screen->name, $task->name],
                $template->message
            );

            $payload = [
                'review_id' => $review->id,
                'screen_id' => $screen->id,
                'notification_type' => 'screen_review',
            ];

            try {
                app(FirebaseService::class)->sendNotification($developer->device_token, $title, $message, $payload);
                app(NotificationRepository::class)->createNotification($developer, $title, $message, $developer->device_token, 'screen_review');
            } catch (\Exception $e) {
                Log::error('Error sending screen_review notification: ' . $e->getMessage());
            }
        }
    }
}
