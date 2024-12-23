<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequest;
use App\Http\Resources\MeetingResource;
use App\Models\AvailableSlot;
use App\Repositories\MeetingRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    private $repository;

    public function __construct(MeetingRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function store(MeetingRequest $request)
    {
        $validated = $request->validated();

        $slot = AvailableSlot::find($validated['slot_id']);
        $requestedStart = Carbon::parse($validated['start_time']);
        $requestedEnd = $requestedStart->copy()->addMinutes($validated['duration']);

        $freeIntervals = $slot->freeIntervals();
        $isAvailable = false;

        foreach ($freeIntervals as $interval) {
            $intervalStart = Carbon::parse($interval['start_time']);
            $intervalEnd = Carbon::parse($interval['end_time']);

            if ($requestedStart >= $intervalStart && $requestedEnd <= $intervalEnd) {
                $isAvailable = true;
                break;
            }
        }

        if (!$isAvailable) {
            return response()->json([
                'status' => false,
                'message' => 'No available time for this meeting.',
            ], 400);
        }

        $jitsiRoom = 'meeting-' . uniqid();
        $jitsiUrl = config('services.jitsi.base_url') . '/' . $jitsiRoom;

        $meeting = $this->repository->create([
            'slot_id' => $validated['slot_id'],
            'client_id' => $validated['client_id'],
            'start_time' => $requestedStart->toTimeString(),
            'end_time' => $requestedEnd->toTimeString(),
            'jitsi_url' => $jitsiUrl,
        ]);

        return response()->json(new MeetingResource($meeting), 201);
    }

    public function getAvailableIntervals($slotId)
    {
        $slot = AvailableSlot::find($slotId);

        if (!$slot) {
            return response()->json([
                'status' => false,
                'message' => 'Slot not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Free intervals retrieved successfully.',
            'data' => $slot->freeIntervals(),
        ]);
    }
}
