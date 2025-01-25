<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'slot_id' => $this->slot_id,
            'client_id' => $this->client_id,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'jitsi_url' => $this->jitsi_url,
            'status' => $this->status,
            'project' => $this->project,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
