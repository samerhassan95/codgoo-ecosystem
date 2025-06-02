<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'task_id' => 'required|exists:tasks,id',
            'status' => 'required|in:not_started,in_progress,completed,canceled',
            'estimated_hours' => 'nullable|integer|min:1',
            'header' => 'nullable|string|max:255',
        ];
    }
}
