<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project' => [
                'id' => $this->project->id ?? null,
                'name' => $this->project->name ?? null,
            ],
            'admin_id' => $this->admin_id,
            'file_path' => asset($this->file_path),
            'status' => $this->status,
            'signed_at' => $this->signed_at,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
