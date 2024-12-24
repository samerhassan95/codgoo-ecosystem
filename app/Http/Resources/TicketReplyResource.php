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
            'admin' => [
                'id' => $this->admin->id,
                'name' => $this->admin->username,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
