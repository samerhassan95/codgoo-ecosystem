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
            'ui'       => 'ui_ux',
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

        $tester = $task
            ?->assignments()
            ->with('employee')
            ->get()
            ->firstWhere(fn($assignment) => $assignment->employee?->role === "tester")
            ?->employee;

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

        $firebase = app(FirebaseService::class);
        $notificationRepo = app(NotificationRepository::class);

        if (
            $tester &&
            $tester->device_token &&
            !($review->creator_type === get_class($tester) && $review->creator_id == $tester->id)
        ) {
            try {
                $firebase->sendNotification($tester->device_token, $title, $message, null, [
                    'screen_review_id' => $review->id,
                    'notification_type' => 'screen_review',
                ]);
                $notificationRepo->createNotification($tester, $title, $message, $tester->device_token, 'screen_review', [
                    'screen_review_id' => $review->id,
                    'notification_type' => 'screen_review',
                ]);
            } catch (\Exception $e) {
                Log::error('Error sending screen_review notification to tester: ' . $e->getMessage());
            }
        } elseif ($developer && $developer->device_token) {
            try {
                $firebase->sendNotification($developer->device_token, $title, $message, null, [
                    'screen_review_id' => $review->id,
                    'notification_type' => 'screen_review',
                ]);
                $notificationRepo->createNotification($developer, $title, $message, $developer->device_token, 'screen_review', [
                    'screen_review_id' => $review->id,
                    'notification_type' => 'screen_review',
                ]);
            } catch (\Exception $e) {
                Log::error('Error sending screen_review notification to developer: ' . $e->getMessage());
            }
        }
    }
}
