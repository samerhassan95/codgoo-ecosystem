<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'department' => $this->department,
            'priority' => $this->priority,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'attachment_url' => $this->attachment ? asset( $this->attachment) : null,
            'replies' => TicketReplyResource::collection($this->whenLoaded('replies')),
        ];
    }
}

