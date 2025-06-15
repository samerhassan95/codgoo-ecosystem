<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeMeetingRequest;
use App\Models\EmployeeMeeting;
use App\Http\Resources\EmployeeMeetingResource;
use App\Services\ZoomService;

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
        }

        return response()->json([
            'status' => true,
            'message' => 'Meeting created successfully with Zoom link.',
            'data' => new EmployeeMeetingResource($meeting->load('participants')),
        ]);
    }

}
