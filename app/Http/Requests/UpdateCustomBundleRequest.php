<?php

// app/Http/Requests/Marketplace/UpdateCustomBundleRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomBundleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization check:
        // 1. Is the user authenticated?
        // 2. Does the user own the CustomBundle being updated? (Check against $this->route('id'))
        // 3. Is the modification window still open (CustomBundle->expires_at)?

        // Example check (simplified for now):
        // $bundle = $this->route('id');
        // return auth()->user()->id === $bundle->customer_id && Carbon::now()->lessThan($bundle->expires_at);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Validation is crucial to ensure the user is upgrading to a valid package/apps.
            'bundleId' => ['required', 'integer', 'exists:bundle_packages,id'],

            'applications' => ['required', 'array', 'min:1'],
            'applications.*' => ['required', 'integer', 'exists:service_apps,id'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'bundle_package_id' => $this->bundleId,
        ]);
    }
}
