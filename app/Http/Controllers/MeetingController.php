<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeetingRequest;
use App\Http\Resources\MeetingResource;
use App\Models\AvailableSlot;
use App\Models\Meeting;
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

    // public function store(MeetingRequest $request)
    // {
    //     $validated = $request->validated();

    //     $slot = AvailableSlot::find($validated['slot_id']);
    //     $requestedStart = Carbon::parse($validated['start_time']);
    //     $requestedEnd = $requestedStart->copy()->addMinutes($validated['duration']);

    //     $freeIntervals = $slot->freeIntervals();
    //     $isAvailable = false;

    //     foreach ($freeIntervals as $interval) {
    //         $intervalStart = Carbon::parse($interval['start_time']);
    //         $intervalEnd = Carbon::parse($interval['end_time']);

    //         if ($requestedStart >= $intervalStart && $requestedEnd <= $intervalEnd) {
    //             $isAvailable = true;
    //             break;
    //         }
    //     }

    //     if (!$isAvailable) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No available time for this meeting.',
    //         ], 400);
    //     }

    //     $jitsiRoom = 'meeting-' . uniqid();
    //     $jitsiUrl = config('services.jitsi.base_url') . '/' . $jitsiRoom;

    //     $meeting = $this->repository->create([
    //         'slot_id' => $validated['slot_id'],
    //         'client_id' => $validated['client_id'],
    //         'start_time' => $requestedStart->toTimeString(),
    //         'end_time' => $requestedEnd->toTimeString(),
    //         'jitsi_url' => $jitsiUrl,
    //     ]);

    //     return response()->json(new MeetingResource($meeting), 201);
    // }

    // public function getAvailableIntervals($slotId)
    // {
    //     $slot = AvailableSlot::find($slotId);

    //     if (!$slot) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Slot not found.',
    //         ], 404);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Free intervals retrieved successfully.',
    //         'data' => $slot->freeIntervals(),
    //     ]);
    // }


    public function store(MeetingRequest $request)
    {
        $validated = $request->validated();

        $duration = $validated['duration'] ?? 60;

        $slot = AvailableSlot::findOrFail($validated['slot_id']);
        $requestedStart = Carbon::parse($validated['start_time']);
        $requestedEnd = $requestedStart->copy()->addMinutes($duration);


        // تحقق من التداخل
        $existingMeetings = $slot->meetings()
            ->where(function ($query) use ($requestedStart, $requestedEnd) {
                $query->whereBetween('start_time', [$requestedStart->toTimeString(), $requestedEnd->toTimeString()])
                      ->orWhereBetween('end_time', [$requestedStart->toTimeString(), $requestedEnd->toTimeString()])
                      ->orWhere(function ($q) use ($requestedStart, $requestedEnd) {
                          $q->where('start_time', '<=', $requestedStart->toTimeString())
                            ->where('end_time', '>=', $requestedEnd->toTimeString());
                      });
            })
            ->exists();

        if ($existingMeetings) {
            return response()->json([
                'status' => false,
                'message' => 'This time slot is already occupied.',
            ], 400);
        }

        // توليد رابط Jitsi إذا كانت الحالة Confirmed
        $jitsiUrl = null;

            $jitsiRoom = 'meeting-' . uniqid();
            $jitsiUrl = config('services.jitsi.base_url') . '/' . $jitsiRoom;

        // إنشاء الاجتماع
        $meeting = $this->repository->create([
            'slot_id' => $validated['slot_id'],
            'client_id' => $validated['client_id'],
            'start_time' => $requestedStart->toTimeString(),
            'end_time' => $requestedEnd->toTimeString(),
            'project_id' => $validated['project_id'],  // تحديد المشروع
            'jitsi_url' => $jitsiUrl,
        ]);

        return response()->json(new MeetingResource($meeting), 201);
    }


    public function getMeetingsForClient(Request $request)
    {
        $client = $request->user();  

        $meetings = Meeting::where('client_id', $client->id)->get();

        return MeetingResource::collection($meetings);
    }

    public function filterMeetingsByStatus(Request $request)
    {
        $client = $request->user();  

        $statusMapping = [
            1 => 'Request Sent',
            2 => 'Confirmed',
            3 => 'Completed',
            4 => 'Canceled',
        ];

        $status = $request->query('status');

        if ($status && isset($statusMapping[$status])) {
            $meetings = Meeting::where('client_id', $client->id)
                ->where('status', $statusMapping[$status])
                ->get();
        } else {
            $meetings = Meeting::where('client_id', $client->id)->get();
        }

        return MeetingResource::collection($meetings);
    }


    public function getMeetingById($id, Request $request)
    {
        $client = $request->user(); 

        $meeting = Meeting::where('client_id', $client->id)->find($id);

        if (!$meeting) {
            return response()->json([
                'status' => false,
                'message' => 'Meeting not found or does not belong to the logged-in client.',
            ], 404);
        }

        return new MeetingResource($meeting);
    }
}
