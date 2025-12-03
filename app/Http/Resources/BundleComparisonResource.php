<?php

// app/Http/Resources/BundleComparisonResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BundleComparisonResource extends JsonResource
{
    /**
     * The $this->resource will be an associative array passed from the controller,
     * e.g., ['bundles' => Collection, 'current_bundle_id' => int]
     */
    public function toArray(Request $request): array
    {
        return [
            'features' => [
                "Name:",
                "Number of Applications",
                "Price",
                "Average Price per App",
                "Support Level"
            ],
            // Use the helper resource to format the bundles
            'bundles' => ComparisonBundleItemResource::collection($this->resource['bundles']),
        ];
    }
}
