<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray($request)
    {
        // Parse attachments
        $attachments = [];
        if (!empty($this->attachments)) {
            if (is_string($this->attachments)) {
                $attachments = json_decode($this->attachments, true) ?? [];
            } elseif (is_array($this->attachments)) {
                $attachments = $this->attachments;
            }
        }

        // Add full URLs to attachments
        $attachmentUrls = array_map(function($path) {
            return [
                'path' => $path,
                'url' => url('storage/' . $path),
                'name' => basename($path),
            ];
        }, $attachments);

        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status,
            'priority' => $this->priority,
            'department' => [
                'id' => $this->department->id ?? null,
                'name' => $this->department->name ?? null,
            ],
            'attachments' => $attachmentUrls,
            'attachments_count' => count($attachmentUrls),
            'replies_count' => $this->replies->count() ?? 0,
            'latest_reply' => $this->latestReply ? [
                'reply' => $this->latestReply->reply,
                'created_at' => $this->latestReply->created_at->format('Y-m-d H:i:s'),
                'is_from_admin' => $this->latestReply->isFromAdmin(),
            ] : null,
            'created_by' => [
                'id' => $this->client->id ?? null,
                'name' => $this->client->name ?? $this->client->username ?? null,
                'email' => $this->client->email ?? null,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}