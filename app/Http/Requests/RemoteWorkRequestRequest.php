<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoteWorkRequestRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'date_from' => 'required|date|after_or_equal:today',
            'date_to' => 'required|date|after_or_equal:date_from',
            'reason' => 'nullable|string|max:500',
            'status' => 'nullable|in:pending,approved,rejected',
            'employee_id' => 'required|exists:employees,id',
        ];
    }
}
