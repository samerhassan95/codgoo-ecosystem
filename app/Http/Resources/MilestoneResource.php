<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MilestoneResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'description' => $this->description,
            'cost' => $this->cost,
            'period' => $this->period,
            'start_date' => $this->start_date,
            'project_id' => $this->project_id,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
        ];
    }
}
