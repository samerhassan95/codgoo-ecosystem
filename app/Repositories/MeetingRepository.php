<?php

namespace App\Repositories;

use App\Models\Meeting;

class MeetingRepository implements MeetingRepositoryInterface
{
    public function getMeetingsWithProject($perPage = 10)
    {
        return Meeting::whereNotNull('project_id')->paginate($perPage);
    }
    

    public function getRequestedMeetingsByProject()
    {
        return Meeting::whereNotNull('project_id')->get();
    }

    public function create(array $data)
    {
        return Meeting::create($data);
    }

    public function getAll()
    {
        return Meeting::all();
    }

    public function getById($id)
    {
        return Meeting::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $meeting = $this->getById($id);
        $meeting->update($data);
        return $meeting;
    }

    public function delete($id)
    {
        $meeting = $this->getById($id);
        $meeting->delete();
        return $meeting;
    }
}
