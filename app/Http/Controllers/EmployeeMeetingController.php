<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeMeetingRequest;
use App\Models\EmployeeMeeting;
use App\Http\Resources\EmployeeMeetingResource;

class EmployeeMeetingController extends Controller
{
    public function store(StoreEmployeeMeetingRequest $request)
    {
        $user = auth('employee')->user() ?? auth('admin')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $meeting = EmployeeMeeting::create([
            'created_by_type' => get_class($user),
            'created_by_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'visibility' => $request->visibility,
            'meeting_url' => $request->meeting_url,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'date' => $request->date,
            'status' => $request->status ?? 'not_started',
        ]);

        if ($request->filled('participant_ids')) {
            $meeting->participants()->sync($request->participant_ids);
        }

        return response()->json([
            'status' => true,
            'message' => 'Meeting created successfully',
            'data' => new EmployeeMeetingResource($meeting->load('participants')),
        ]);
    }
}
