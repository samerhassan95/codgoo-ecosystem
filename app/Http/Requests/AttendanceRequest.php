<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'total_hours' => 'required|numeric|min:0|max:24',
            'date' => 'required|date',
            'employee_id' => 'required|exists:users,id',
        ];
    }
}
