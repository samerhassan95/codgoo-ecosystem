<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'task_id' => 'required|exists:tasks,id',
            'dev_mode' => 'nullable|boolean',
            'implemented' => 'nullable|boolean',
            'frontend_approved' => 'nullable|boolean',
            'integrated' => 'nullable|boolean',
            'screen_code' => 'nullable|string|max:255',
            'estimated_hours' => 'nullable|integer|min:0',
            'comment' => 'nullable|string|max:1000',
        ];
    }
}
