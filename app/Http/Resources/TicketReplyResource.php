<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reply' => $this->reply,
            'creator' => [
                'id' => $this->creator->id,
                'name' => $this->creator instanceof \App\Models\Admin ? $this->creator->username : $this->creator->name,
                'type' => class_basename($this->creator), // To differentiate between Client or Admin
            ],
            
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
