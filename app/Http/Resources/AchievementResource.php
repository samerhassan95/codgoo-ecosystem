<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchievementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task' => new TaskResource($this->whenLoaded('task')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'attendance' => new AttendanceResource($this->whenLoaded('attendance')),
            'achievement_description' => $this->achievement_description,
            'submitted_at' => $this->submitted_at,
            'achievement_type' => $this->achievement_type,
        ];
    }
}

