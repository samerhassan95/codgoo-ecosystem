<?php

namespace App\Observers;

use App\Models\ImplementedApiReview;
use App\Models\NotificationTemplate;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class ImplementedApiReviewObserver
{
    public function created(ImplementedApiReview $review)
    {
        $backend = $api->screen->task
                    ?->assignments()
                    ->with('employee') 
                    ->get()
                    ->firstWhere(fn($assignment) => $assignment->employee?->role === 'back_end')
                    ?->employee;
        if ($backend && $backend->device_token) {
            $template = NotificationTemplate::where('type', 'api_review_added')->first();

            if (!$template) {
                Log::error('Notification template "api_review_added" not found.');
                return;
            }

            $title = $template->title;
            $message = str_replace(
                ['{endpoint}', '{screen_name}'],
                [$review->implementedApi->requestedApi->endpoint, $review->implementedApi->requestedApi->screen->name],
                $template->message
            );

            $payload = [
                'review_id' => $review->id,
                'notification_type' => 'api_review_added',
            ];

            try {
                app(FirebaseService::class)->sendNotification($backend->device_token, $title, $message, $payload);
                app(NotificationRepository::class)->createNotification($backend, $title, $message, $backend->device_token, 'api_review_added', $payload);
            } catch (\Exception $e) {
                Log::error('Error sending api_review_added notification: ' . $e->getMessage());
            }
        }
    }
}
