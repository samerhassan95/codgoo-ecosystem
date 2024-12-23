<?php

namespace App\Repositories;

use App\Models\Meeting;

class MeetingRepository implements MeetingRepositoryInterface
{
    public function create(array $data)
    {
        return Meeting::create($data);
    }

    public function getBySlot($slotId)
    {
        return Meeting::where('slot_id', $slotId)->get();
    }
}
