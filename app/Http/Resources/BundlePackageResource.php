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
            'apps_count'=>$this->apps_count,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'features' => $this->features, // JSON cast as array in Model
            'savings' => [
                'percentage' => (int) $this->savings_percentage,
                'text' => $this->savings_text,
            ],
            'prices' => $this->prices->map(function($price) {
    return [
        'id' => $price->id,
        'name' => $price->name,
        'amount' => $price->amount / 100,
        'currency' => $price->currency,
        'duration_days' => $price->duration_days,
    ];
}),
            'badges' => $this->badges ?? [], // JSON cast as array in Model
        ];
    }
}
