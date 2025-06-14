<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScreenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'task' => new TaskResource($this->whenLoaded('task')),
            'dev_mode' => $this->dev_mode,
            'implemented' => $this->implemented,
            'integrated' => $this->integrated,
            'screen_code' => $this->screen_code,
            'estimated_hours' => $this->estimated_hours,
            'comment' => $this->comment,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
