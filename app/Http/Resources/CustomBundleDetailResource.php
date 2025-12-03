<?php

// app/Http/Resources/CustomBundleDetailResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CustomBundleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Calculate modification window details
        $now = Carbon::now();
        $canModify = $now->lessThan($this->expires_at);
        $diff = $now->diff($this->expires_at);
        $bundlePackage = $this->bundlePackage;

        return [
            'id' => $this->id,
            'bundle' => [
                'name' => $bundlePackage->name,
                'applicationsPurchased' => $this->applications->count(),
                'purchasedAt' => $this->purchased_at->toISOString(),
                'purchasedAtLabel' => 'Purchased ' . $this->purchased_at->format('F j, Y'),
                'totalPrice' => [
                    'amount' => $this->total_price_amount / 100,
                    'currency' => $this->total_price_currency,
                ],
                // Pull savings data from the related BundlePackage model
                'savings' => [
                    'percentage' => (int) $bundlePackage->savings_percentage,
                    'text' => $bundlePackage->savings_text,
                ],
            ],
            // Use the ServiceAppResource to format the included applications
            'applications' => ServiceAppResource::collection($this->applications),

            'modification' => [
                'canModify' => $canModify,
                'remainingDays' => $canModify ? $diff->days : 0,
                'remainingHours' => $canModify ? $diff->h : 0,
            ],
        ];
    }
}
