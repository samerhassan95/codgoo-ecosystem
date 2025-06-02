<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskAssignmentResource extends JsonResource
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
            'status' => $this->status,
            'estimated_hours' => $this->estimated_hours,
            'header' => $this->header,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
