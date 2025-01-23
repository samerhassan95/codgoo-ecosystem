<?php

namespace App\Http\Controllers;

use App\Models\AvailableSlot;
use Illuminate\Http\Request;

class AvailableSlotController extends Controller
{
    public function getAvailableSlotsGroupedByDate()
    {
        $slots = AvailableSlot::orderBy('date')->get()->groupBy('date');
        
        return response()->json([
            'status' => true,
            'message' => 'Available slots grouped by date',
            'data' => $slots
        ]);
    }
}
