<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScreenReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'screen' => new ScreenResource($this->whenLoaded('screen')),
            'comment' => $this->comment,
        ];
    }
}
