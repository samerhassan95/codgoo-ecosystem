<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAchievementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
             'achievement_description' => 'nullable|string',
            'issues_notes'            => 'nullable|string',
            'attendance_id'           => 'required|exists:attendances,id',
            'attachments.*'           => 'nullable|file|max:5120',
        ];
    }
}
