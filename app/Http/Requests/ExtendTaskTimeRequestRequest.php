<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendTaskTimeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'task_id' => 'required|exists:tasks,id',
            'new_deadline' => 'required|date|after:today',
            'reason' => 'nullable|string|max:500',
        ];
    }
}
