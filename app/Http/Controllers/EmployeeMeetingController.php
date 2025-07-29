<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeMeetingRequest;
use App\Models\EmployeeMeeting;
use App\Http\Resources\EmployeeMeetingResource;
use App\Services\ZoomService;
use App\Models\NotificationTemplate;
use App\Repositories\NotificationRepository;
use App\Services\FirebaseService;
use App\Models\Employee;

class EmployeeMeetingController extends Controller
{

    public function store(EmployeeMeetingRequest $request, ZoomService $zoomService)
    {
        $user = auth('employee')->user() ?? auth('admin')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $data = $request->validated();
        $data['created_by_type'] = get_class($user);
        $data['created_by_id'] = $user->id;

        try {
            $startTime = now()->addMinutes(5)->toIso8601String();
            $zoomTitle = is_array($data['title']) ? ($data['title']['en'] ?? reset($data['title'])) : $data['title'];

            $zoomMeeting = $zoomService->createMeeting(
                $zoomTitle,
                $startTime,
                60
            );

            if (isset($zoomMeeting['join_url'])) {
                $data['meeting_url'] = $zoomMeeting['join_url'];
                $data['zoom_meeting_id'] = $zoomMeeting['id'] ?? null;
                $data['zoom_meeting_passcode'] = $zoomMeeting['password'] ?? null;
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Zoom meeting creation failed: ' . $e->getMessage(),
            ], 500);
        }

        $meeting = EmployeeMeeting::create($data);

        if (!empty($data['participant_ids'])) {
            $meeting->participants()->sync($data['participant_ids']);
            $this->sendMeetingNotificationToParticipants($meeting, $data['participant_ids']);
        }

        return response()->json([
            'status' => true,
            'message' => 'Meeting created successfully with Zoom link.',
            'data' => new EmployeeMeetingResource($meeting->load('participants')),
        ]);
    }

    private function sendMeetingNotificationToParticipants(EmployeeMeeting $meeting, array $participantIds)
    {
        $template = NotificationTemplate::where('type', 'employee_meeting_created')->first();
        if (!$template) {
            \Log::error('Notification template "employee_meeting_created" not found.');
            return;
        }

        $title = $template->title;
        $message = str_replace(
            ['{title}', '{start_time}'],
            [$meeting->title['en'] ?? $meeting->title, $meeting->created_at->format('Y-m-d H:i')],
            $template->message
        );

        $employees = Employee::whereIn('id', $participantIds)
            ->whereNotNull('device_token')
            ->get();

        foreach ($employees as $employee) {
            try {
                $dataPayload = [
                    'employee_meeting_id' => $meeting->id,
                    'notification_type' => 'employee_meeting_created',
                ];

                app(FirebaseService::class)->sendNotification($employee->device_token, $title, $message, $dataPayload);
                app(NotificationRepository::class)->createNotification($employee, $title, $message, $employee->device_token, 'employee_meeting_created');

            } catch (\Exception $e) {
                \Log::error('Error sending employee meeting notification: ' . $e->getMessage());
            }
        }
    }

}
