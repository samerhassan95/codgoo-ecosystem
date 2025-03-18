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
            'comment' => 'required|string',
        ];
    }
}
