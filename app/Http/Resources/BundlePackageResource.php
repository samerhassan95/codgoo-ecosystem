<?php

// app/Http/Resources/BundlePackageResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BundlePackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'price' => [
                'amount' => $this->price_amount / 100,
                'currency' => $this->price_currency,
            ],
            'features' => $this->features, // JSON cast as array in Model
            'savings' => [
                'percentage' => (int) $this->savings_percentage,
                'text' => $this->savings_text,
            ],
            'badges' => $this->badges ?? [], // JSON cast as array in Model
        ];
    }
}
