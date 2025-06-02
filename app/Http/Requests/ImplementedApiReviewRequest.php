<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImplementedApiReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'implemented_api_id' => 'required|exists:implemented_apis,id',
            'review' => 'required|string',
            'creator_id' => 'required|integer',
            'creator_type' => 'required|string|in:App\Models\Admin,App\Models\Employee',
        ];
    }
}
