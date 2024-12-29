<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GalleryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'galleriable_id' => $this->galleriable_id,
            'galleriable_type' => $this->galleriable_type,
            'image_path' => $this->image_path ? asset($this->image_path) : null,  
        ];
    }
}
