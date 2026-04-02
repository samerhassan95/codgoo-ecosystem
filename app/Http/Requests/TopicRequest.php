<?php

namespace App\Http\Requests;

use App\Enum\SectionEnum;
use Illuminate\Foundation\Http\FormRequest;

class TopicRequest extends FormRequest
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
            'section_id' => 'required|integer|in:' . implode(',', array_keys(SectionEnum::getList())),
            'header' => 'required|string|max:255',
            'description' => 'nullable|string',
            'gallery' => 'array', 
            'date' => 'required|date',
            'gallery.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
