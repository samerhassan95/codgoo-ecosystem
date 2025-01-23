<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon; 

class AvailableSlot extends Model
{
    use HasFactory;

    protected $guarded = [];

    // public function freeIntervals()
    // {
    //     $meetings = $this->meetings()->orderBy('start_time')->get();
    //     $freeIntervals = [];

    //     $currentStart = Carbon::parse($this->start_time);
    //     $endTime = Carbon::parse($this->end_time);

    //     foreach ($meetings as $meeting) {
    //         $meetingStart = Carbon::parse($meeting->start_time);
    //         $meetingEnd = Carbon::parse($meeting->end_time);

    //         // Add free interval before this meeting
    //         if ($currentStart < $meetingStart) {
    //             $freeIntervals[] = [
    //                 'start_time' => $currentStart->toTimeString(),
    //                 'end_time' => $meetingStart->toTimeString(),
    //             ];
    //         }

    //         // Update the current start time to the end of the current meeting
    //         $currentStart = $meetingEnd;
    //     }

    //     // Add remaining free interval after the last meeting
    //     if ($currentStart < $endTime) {
    //         $freeIntervals[] = [
    //             'start_time' => $currentStart->toTimeString(),
    //             'end_time' => $endTime->toTimeString(),
    //         ];
    //     }

    //     return $freeIntervals;
    // }
    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'slot_id');
    }
}
