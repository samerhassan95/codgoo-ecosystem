<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'nullable|in:private,public',
            'meeting_url' => 'nullable|url',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s|after_or_equal:start_time',
            'date' => 'required|date',
            'status' => 'in:not_started,scheduled,completed,canceled',
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:employees,id',
        ];
    }
}
