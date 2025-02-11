<?php

namespace App\Http\Controllers;

use App\Models\AvailableSlot;
use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AvailableSlotController extends Controller
{
    // public function getAvailableSlotsGroupedByDate(Request $request)
    // {
    //     $validated = $request->validate([
    //         'date' => 'required|date',
    //     ]);

    //     $date = $validated['date'];

    //     $slots = AvailableSlot::where('date', $date)
    //         ->orderBy('start_time')
    //         ->get()
    //         ->groupBy('date');

    //     if ($slots->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No available slots found for the given date.',
    //             'data' => [],
    //         ], 404);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Available slots for the given date',
    //         'data' => $slots->mapWithKeys(fn($value, $key) => [
    //             'available' => $value->sortBy('start_time')->values(),
    //         ]),
    //     ]);
    // }

    public function getAvailableSlotsGroupedByDate(Request $request)
{
    $validated = $request->validate([
        'date' => 'required|date',
    ]);

    $date = Carbon::parse($validated['date']);
    $today = Carbon::today();

    // Check if the requested date is in the past
    if ($date->lt($today)) {
        return response()->json([
            'status' => false,
            'message' => 'You cannot request a meeting in the past.',
            'data' => [],
        ], 400);
    }

    $slots = AvailableSlot::where('date', $date->toDateString())
        ->orderBy('start_time')
        ->get();

    if ($slots->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No available slots found for the given date.',
            'data' => [],
        ], 404);
    }

    $formattedSlots = [];

    foreach ($slots as $slot) {
        $currentStart = Carbon::parse($slot->start_time);
        $endTime = Carbon::parse($slot->end_time);

        while ($currentStart < $endTime) {
            $nextHour = $currentStart->copy()->addHour();

            if ($nextHour > $endTime) {
                $nextHour = $endTime;
            }

            $meetingExists = Meeting::where('slot_id', $slot->id)
                ->where(function ($query) use ($currentStart, $nextHour) {
                    $query->whereBetween('start_time', [$currentStart->toTimeString(), $nextHour->toTimeString()])
                          ->orWhereBetween('end_time', [$currentStart->toTimeString(), $nextHour->toTimeString()])
                          ->orWhere(function ($q) use ($currentStart, $nextHour) {
                              $q->where('start_time', '<=', $currentStart->toTimeString())
                                ->where('end_time', '>=', $nextHour->toTimeString());
                          });
                })
                ->exists();

            $formattedSlots[] = [
                'slot_id' => $slot->id,
                'date' => $slot->date,
                'start_time' => $currentStart->toTimeString(),
                'end_time' => $nextHour->toTimeString(),
                'status' => $meetingExists ? 'Booked' : 'Available',
            ];

            $currentStart = $nextHour;
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'Available slots for the given date',
        'data' => $formattedSlots,
    ]);
}

}
