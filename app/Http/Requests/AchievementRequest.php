<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_id' => 'required|exists:tasks,id',
            'created_by' => 'required|exists:employees,id',
            'attendance_id' => 'required|exists:attendances,id',
            'achievement_description' => 'required|string',
            'submitted_at' => 'nullable|date',
            'achievement_type' => 'required|exists:achievement_types,id',
        ];
    }
}
