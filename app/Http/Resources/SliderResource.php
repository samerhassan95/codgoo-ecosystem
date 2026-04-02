<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SliderResource extends JsonResource
{
public function toArray($request)
{
$imagePaths = is_array($this->image) ? $this->image : [];

    return [
        'id'      => $this->id,
        'product' => new ProductResource($this->product),
        'images'  => array_map(function($path) {
            return asset($path);
        }, $imagePaths),
    ];
}
}
