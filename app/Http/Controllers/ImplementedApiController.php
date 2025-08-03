<?php

namespace App\Http\Controllers;

use App\Repositories\ImplementedApiRepositoryInterface;
use Illuminate\Http\Request;
use App\Models\ImplementedApi;
use App\Models\RequestedApi;
use App\Models\NotificationTemplate;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

class ImplementedApiController extends BaseController
{
    private $repository;

    public function __construct(ImplementedApiRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'requested_api_ids' => 'required|array',
            'requested_api_ids.*' => 'exists:requested_apis,id',
            'postman_collection_url' => 'nullable|string',
        ]);

        $status = 'complete';
        $url = $validated['postman_collection_url'] ?? null;

        foreach ($validated['requested_api_ids'] as $apiId) {
            $implemented = ImplementedApi::firstOrCreate(
                ['requested_api_id' => $apiId],
                ['postman_collection_url' => $url]
            );

            $api = RequestedApi::with('screen.task.assignments.employee')->find($apiId);
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
                    continue;
                }

                $title = $template->title;
                $message = str_replace(
                    ['{api_name}', '{screen_name}', '{task_name}'],
                    [$api->name, $api->screen->name, $api->screen->task->name],
                    $template->message
                );

                $payload = [
                    'api_id' => $api->id,
                    'screen_id' => $api->screen->id,
                    'notification_type' => 'api_implemented',
                ];

                try {
                    app(FirebaseService::class)->sendNotification($tester->device_token, $title, $message, $payload);
                    app(NotificationRepository::class)->createNotification($tester, $title, $message, $tester->device_token, 'api_implemented');
                } catch (\Exception $e) {
                    Log::error('Error sending api_implemented notification: ' . $e->getMessage());
                }
            }
        }

        return response()->json([
            'message' => 'APIs marked as implemented successfully.',
        ], 201);
    }

    public function markAsTested(Request $request)
    {
        $request->validate([
            'screen_id' => 'required|exists:screens,id',
        ]);

        $implementedApiIds = ImplementedApi::whereIn('requested_api_id', function ($query) use ($request) {
            $query->select('id')
                ->from('requested_apis')
                ->where('screen_id', $request->screen_id);
        })->pluck('id');

        ImplementedApi::whereIn('id', $implementedApiIds)->update(['status' => 'tested']);

        return response()->json([
            'status' => true,
            'message' => 'All implemented APIs for the screen marked as tested successfully.',
        ]);
    }

}
