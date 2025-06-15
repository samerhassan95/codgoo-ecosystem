<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OvertimeRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'employee' => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
            ],
            'date' => $this->date,
            'number_of_hours' => $this->number_of_hours,
            'status' => $this->status,
            'work_description' => $this->work_description,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
