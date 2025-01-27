<?php

namespace App\Http\Controllers;

use App\Models\AvailableSlot;
use Illuminate\Http\Request;

class AvailableSlotController extends Controller
{
    public function getAvailableSlotsGroupedByDate(Request $request)
{
    $validated = $request->validate([
        'date' => 'required|date',
    ]);

    $date = $validated['date'];

    $slots = AvailableSlot::where('date', $date)
        ->orderBy('date')
        ->get()
        ->groupBy('date');

    if ($slots->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No available slots found for the given date.',
            'data' => [],
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => 'Available slots for the given date',
        'data' => $slots,
    ]);
}

}
