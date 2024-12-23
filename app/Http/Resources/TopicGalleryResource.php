<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'milestone_id' => $this->milestone_id,
            'project_id' => $this->project_id,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_proof' => $this->payment_proof ? asset($this->payment_proof) : null,
            'due_date' => $this->due_date,
        ];
    
    }
}
