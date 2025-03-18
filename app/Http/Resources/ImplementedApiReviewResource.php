<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImplementedApiReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'implemented_api' => new ImplementedApiResource($this->whenLoaded('implementedApi')),
            'review' => $this->review,
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
