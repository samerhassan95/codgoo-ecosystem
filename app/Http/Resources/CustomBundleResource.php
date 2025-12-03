<?php

// app/Http/Resources/CustomBundleResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomBundleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bundleId' => $this->bundle_package_id,
            // Assuming the applications array is not returned in the simple creation response
            'applications' => $this->applications->pluck('id'), // Return IDs of attached apps
            'totalPrice' => [
                'amount' => $this->total_price_amount / 100,
                'currency' => $this->total_price_currency,
            ],
            'createdAt' => $this->created_at->toISOString(),
            'status' => $this->status,
        ];
    }
}
