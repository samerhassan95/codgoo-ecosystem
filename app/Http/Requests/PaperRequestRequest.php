<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaperRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'header' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'employee_id' => 'required|exists:employees,id',
        ];
    }
}
