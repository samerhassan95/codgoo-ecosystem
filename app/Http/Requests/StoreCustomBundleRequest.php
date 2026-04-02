<?php

// app/Http/Requests/Marketplace/StoreCustomBundleRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomBundleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Typically, you would check if the user is authenticated here.
        // For example: return auth()->check();
return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Bundle
            'bundleId' => [
                'required',
                'integer',
                'exists:bundle_packages,id'
            ],

            // 🔥 PRICE PLAN (monthly / yearly / etc)
            'priceId' => [
                'required',
                'integer',
                'exists:bundle_package_prices,id'
            ],

            // Optional applications
            'applications' => ['sometimes', 'array'],
            'applications.*' => ['integer', 'exists:service_apps,id'],
        ];
    }

    /**
     * Prepare the data for validation.
     * Use this to map frontend keys (camelCase) to backend keys (snake_case)
     */
    protected function prepareForValidation(): void
    {
        // Map the 'bundleId' to 'bundle_package_id' for easier access in the controller later
        $this->merge([
            'bundle_package_id' => $this->bundleId,
        ]);
    }
}
