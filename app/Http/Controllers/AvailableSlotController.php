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
        $twoDaysLater = $today->copy()->addDays(2);

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
                        $query->where('start_time', '<', $nextHour->toTimeString())
                            ->where('end_time', '>', $currentStart->toTimeString());
                    })
                    ->exists();

                $forceUnavailable = $date->lte($twoDaysLater);

                $formattedSlots[] = [
                    'slot_id' => $slot->id,
                    'date' => $slot->date,
                    'start_time' => $currentStart->toTimeString(),
                    'end_time' => $nextHour->toTimeString(),
                    'status' => $forceUnavailable ? false : !$meetingExists, 
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $slot = AvailableSlot::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Slot created successfully',
            'data' => $slot
        ], 201);
    }

    public function destroy($id)
    {
        $slot = AvailableSlot::find($id);

        if (!$slot) {
            return response()->json([
                'status' => false,
                'message' => 'Slot not found'
            ], 404);
        }

        $slot->delete();

        return response()->json([
            'status' => true,
            'message' => 'Slot deleted successfully'
        ]);
    }

}
