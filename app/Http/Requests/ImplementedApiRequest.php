<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImplementedApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'requested_api_id' => 'required|exists:requested_apis,id',
            'postman_collection_url' => 'nullable|url',
            'status' => 'required|in:pending,complete,tested',
        ];
    }
}
