<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomBundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or add authorization logic
    }

    public function rules(): array
    {
        return [
            'bundleId' => ['required', 'integer', 'exists:bundle_packages,id'],
            'applications' => ['sometimes', 'array'], // optional
            'applications.*' => ['integer', 'exists:service_apps,id'], // ✅ corrected table
            'priceId' => ['required', 'integer', 'exists:bundle_package_prices,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'bundleId.required' => 'Bundle ID is required.',
            'bundleId.exists' => 'Selected bundle does not exist.',
            'applications.array' => 'Applications must be an array.',
            'applications.*.exists' => 'One or more selected applications do not exist.',
        ];
    }
}
