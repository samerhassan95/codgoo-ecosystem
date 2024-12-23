<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            
            'milestone_id' => 'required|exists:milestones,id',
            'project_id' => 'required|exists:projects,id',
            'status' => 'required|in:paid,unpaid',
            'payment_method' => 'nullable|in:bank_transfer,online',
            'payment_proof' => 'nullable|image|max:2048',
            'due_date' => 'required|date',
        ];
    }
}
