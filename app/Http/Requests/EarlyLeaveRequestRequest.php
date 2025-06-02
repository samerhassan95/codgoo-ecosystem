<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EarlyLeaveRequestRequest extends FormRequest
{
  
    public function authorize(): bool
    {
        return true;
    }

  
    public function rules(): array
    {
        return [
            'date' => 'required|date|after_or_equal:today',
            'leave_time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:500',
            'employee_id' => 'required|exists:employees,id',
            
        ];
    }
}
