<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'attendance_id' => 'required|exists:attendances,id',
            'ip_address' => 'required|ip',
            'check_in_time' => 'required|date',
            'check_out_time' => 'nullable|date',
            'is_in_office' => 'boolean'
        ];
    }
}
