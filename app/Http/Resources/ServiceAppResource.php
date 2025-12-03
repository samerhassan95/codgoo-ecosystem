<?php

// app/Http/Resources/ServiceAppResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceAppResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'description' => $this->description,
            'price' => [
                'amount' => $this->price_amount / 100, // Convert cents back to currency unit
                'currency' => $this->price_currency,
            ],
            'rating' => [
                'average' => (float) $this->rating_average,
                'scale' => (int) $this->rating_scale,
                'reviewsCount' => (int) $this->reviews_count,
            ],
            'icon' => [
                'type' => $this->icon_type,
                'url' => $this->icon_url,
                'alt' => $this->icon_alt,
            ],
        ];
    }
}
