<?php

// app/Http/Resources/CustomBundleResource.php


namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ServiceApp;

class CustomBundleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // ✅ Resolve applications source
        $applications = $this->status === 'pending'
            ? ServiceApp::whereIn('id', $this->requested_app_ids ?? [])->get()
            : $this->applications;

        return [
            'id' => $this->id,
            'bundleId' => $this->bundle_package_id,

            'applications' => $applications->map(function ($app) {
                return [
                    'id' => $app->id,
                    'name' => $app->name,
                    'externalProfileUrl' =>
                        $this->status === 'active'
                            ? ($app->pivot->external_profile_url ?? null)
                            : null,
                ];
            }),
                    'plan' => [
                        'id' => $this->bundle_price_id,
                        'name' => $this->meta['plan_name'] ?? null,
                        'amount' => $this->total_price_amount,
                        'currency' => $this->total_price_currency,
                        'expires_at' => $this->expires_at,
                    ],

            'createdAt' => $this->created_at,
            'status' => $this->status,
        ];
    }
}
