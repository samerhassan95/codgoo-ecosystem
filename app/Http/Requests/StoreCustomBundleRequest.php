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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // bundleId validation (must exist in the BundlePackages table)
            'bundleId' => ['required', 'integer', 'exists:bundle_packages,id'],

            // Applications validation (must be an array of valid ServiceApp IDs)
            'applications' => ['required', 'array', 'min:1'],
            'applications.*' => ['required', 'integer', 'exists:service_apps,id'],

            // Customer ID validation (assuming the Customer ID is passed explicitly)
            // Note: If you use Laravel Sanctum/Passport, you might get this from auth() instead.
            'customer.id' => ['required', 'integer', 'exists:clients,id'],
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
