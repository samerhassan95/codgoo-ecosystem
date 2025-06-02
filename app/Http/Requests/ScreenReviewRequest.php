<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScreenReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'screen_id' => 'required|exists:screens,id',
            'comment' => 'required|string|max:1000',
            'review_type' => 'required|in:ui,frontend,both',
            'created_by' => 'required|exists:users,id',
        ];
    }
}
