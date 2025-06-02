<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectGeneralNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'type' => 'required|in:UI_UX,Front_end,Back_end,Mobile_App',
            'description' => 'nullable|string|max:2000',
        ];
    }
}
