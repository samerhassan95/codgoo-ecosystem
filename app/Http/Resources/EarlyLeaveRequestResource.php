<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarlyLeaveRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
           'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
            ] : null,
            'date' => $this->date,
            'leave_time' => $this->leave_time,
            'reason' => $this->reason,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
