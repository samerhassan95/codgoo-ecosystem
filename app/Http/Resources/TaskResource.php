<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'priority' => $this->priority,
        ];
    }
}
