<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_id' => $this->attendance_id,
            'ip_address' => $this->ip_address,
            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'is_in_office' => $this->is_in_office,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
