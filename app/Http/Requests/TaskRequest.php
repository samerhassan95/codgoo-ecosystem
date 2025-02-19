<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|string|in:not_started,in_progress,awaiting_feedback,completed,canceled,testing',
            'priority' => 'required|string|in:High,Low,Medium',
            'milestone_id' => 'required|exists:milestones,id',
            'assigned_to' => 'nullable|exists:employees,id',
        ];
    }
}
