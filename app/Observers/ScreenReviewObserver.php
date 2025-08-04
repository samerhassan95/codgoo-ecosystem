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

        $reviewRoleMap = [
            'frontend' => 'front_end',
            'backend'  => 'back_end',
            'mobile'   => 'mobile',
        ];

        $targetRole = $reviewRoleMap[$review->review_type] ?? null;

        $developer = $task
            ?->assignments()
            ->with('employee')
            ->get()
            ->firstWhere(fn($assignment) => $assignment->employee?->role === $targetRole)
            ?->employee;

        if ($developer && $developer->device_token) {
            $template = NotificationTemplate::where('type', 'screen_review')->first();

            if (!$template) {
                Log::error('Notification template "screen_review" not found.');
                return;
            }

            $title = $template->title;
            $message = str_replace(
                ['{screen_name}', '{task_name}'],
                [$screen->name, $task->name],
                $template->message
            );

            $payload = [
                'review_id' => (string) $review->id,
                'screen_id' => (string) $screen->id,
                'notification_type' => 'screen_review',
            ];

            try {
                app(FirebaseService::class)->sendNotification($developer->device_token, $title, $message);
                app(NotificationRepository::class)->createNotification($developer, $title, $message, $developer->device_token, 'screen_review');
            } catch (\Exception $e) {
                Log::error('Error sending screen_review notification: ' . $e->getMessage());
            }
        }
    }
}
