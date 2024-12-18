<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SliderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'products' => $this->products->map(function ($product) {
                return [
                    'product' => $product,
                    'image' => asset($product->pivot->image),
                ];
            }),
        ];
    }
}
