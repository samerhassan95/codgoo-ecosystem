<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtendTaskTimeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee' => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
            ],
            'task' => [
                'id' => $this->task->id,
                'title' => $this->task->title,
            ],
            'new_deadline' => $this->new_deadline->format('Y-m-d H:i:s'),
            'reason' => $this->reason,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
