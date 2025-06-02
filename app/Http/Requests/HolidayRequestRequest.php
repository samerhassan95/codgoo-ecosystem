<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HolidayRequestRequest extends FormRequest
{
  
    public function authorize(): bool
    {
        return true; 
    }

   
    public function rules(): array
    {
        return [
            'description' => 'nullable|string|max:500',
            'date_from' => 'required|date|after_or_equal:today',
            'date_to' => 'required|date|after_or_equal:date_from',
            'employee_id' => 'required|exists:employees,id',
        ];
    }
}
