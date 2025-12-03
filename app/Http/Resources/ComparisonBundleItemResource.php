<?php

// app/Http/Resources/ComparisonBundleItemResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComparisonBundleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // These fields would typically be calculated/stored on the model or passed in
        $numberOfApplications = $this->numberOfApplications ?? 0;
        $supportLevel = $this->supportLevel ?? 'N/A';
        $averagePrice = $numberOfApplications > 0
            ? round(($this->price_amount / 100) / $numberOfApplications)
            : 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'isCurrent' => (bool) $this->isCurrent, // Assumed property passed during fetching
            'numberOfApplications' => $numberOfApplications,
            'price' => [
                'amount' => $this->price_amount / 100,
                'currency' => $this->price_currency,
            ],
            'averagePricePerApp' => [
                'amount' => $averagePrice,
                'currency' => $this->price_currency,
            ],
            'supportLevel' => $supportLevel,
        ];
    }
}
