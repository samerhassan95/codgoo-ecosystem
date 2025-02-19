<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductMediaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'file_path' => asset($this->file_path),
            'type' => $this->type,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}

