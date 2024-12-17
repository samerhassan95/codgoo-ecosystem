<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectAddonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'Project_id' => $this->project_id,
            'addon_id' => $this->addon_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
