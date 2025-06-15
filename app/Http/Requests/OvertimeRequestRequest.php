<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OvertimeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date|after_or_equal:today',
            'number_of_hours' => 'required|integer|min:1',
            'status' => 'nullable|in:pending,approved,rejected',
            'work_description' => 'nullable|string',
        ];
    }
}
