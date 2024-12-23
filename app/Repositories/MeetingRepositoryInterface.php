<?php

namespace App\Repositories;

interface MeetingRepositoryInterface
{
    public function create(array $data);
    public function getBySlot($slotId);
}

