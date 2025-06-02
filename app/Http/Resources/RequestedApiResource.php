<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestedApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'screen' => new ScreenResource($this->whenLoaded('screen')),
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'request_body' => $this->request_body,
            'response_structure' => $this->response_structure,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

        ];
    }
}
