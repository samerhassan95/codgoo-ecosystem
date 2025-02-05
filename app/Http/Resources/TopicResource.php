<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TopicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return 
        [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'section_label' => $this->section_label,
            'header' => $this->header,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'galleries' => $this->galleries->map(function ($gallery) 
            {
                return asset($gallery->image_path); 
            }
        ),
        ];

    }
}
