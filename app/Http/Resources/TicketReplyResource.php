<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketReplyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'reply' => $this->reply,
'attachments' => collect(
    is_array($this->attachments)
        ? $this->attachments
        : json_decode($this->attachments, true)
)->map(function ($path) {
    return asset('storage/' . $path);
}),
            'creator' => [
                'id' => $this->creator->id ?? null,
                'name' => $this->creator->name ?? $this->creator->username ?? 'Support Team',
                'type' => $this->isFromAdmin() ? 'admin' : 'client',
            ],
            'is_from_admin' => $this->isFromAdmin(),
            'is_from_client' => $this->isFromClient(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}