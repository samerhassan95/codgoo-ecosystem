<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SliderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product' => new ProductResource($this->product), 
            'image' => asset($this->image),
        ];
    }
}
