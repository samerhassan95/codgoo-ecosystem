<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestedApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'screen_id' => 'required|exists:screens,id',
            'endpoint' => 'required|string',
            'method' => 'required|in:GET,POST,PUT,DELETE',
            'request_body' => 'nullable|json',
            'response_structure' => 'nullable|json',
        ];
    }
}
