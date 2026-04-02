<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\JWTService;

class ServiceAppResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'category' => $this->category,
            'description' => $this->description,
            'overview' => $this->overview,
            'stats' => [
                'installs' => $this->installs,
                'version' => $this->version,
                'size' => $this->size,
                'lastUpdate' => optional($this->last_update)->format('M d, Y'),
            ],

            'pricing' => [
                'type' => $this->pricing_type,
                'hasFreeTrial' => $this->has_free_trial,
                'amount' => $this->price_amount / 100,
                'currency' => $this->price_currency,
            ],

            'rating' => [
                'average' => (float) $this->rating_average,
                'scale' => (int) $this->rating_scale,
                'reviewsCount' => (int) $this->reviews_count,
            ],

            'features' => $this->features,            // LONGTEXT
            'screenshots' => $this->screenshots ?? [], // JSON array
            'integrations' => $this->integrations,

            'support' => [
                'documentation' => $this->documentation_url,
                'helpCenter' => $this->help_center_url,
                'contact' => $this->contact_url,
            ],

            'icon' => [
                'type' => $this->icon_type,
                'url' => $this->icon_url,
                'alt' => $this->icon_alt,
            ],

            'appDetails' => [
                'appUrl' => $this->app_url,
                'ssoEntrypoint' => $this->sso_entrypoint,
            ],
        ];
    }

}
