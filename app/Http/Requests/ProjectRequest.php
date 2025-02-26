<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Modify this if you need specific authorization logic
    }

    public function rules()
    {
        $rules = [
            'product_id' => 'nullable|exists:products,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $user = auth()->user();
                    if ($user instanceof \App\Models\Client && $value !== null) {
                        $fail('Only Admin can set the price.');
                    }
                },
            ],
            'note' => 'nullable|string|max:1000',
            'status' => 'string|in:approved,not_approved,canceled',
            'client_id' => 'required|exists:clients,id', // Ensure the client exists
            'category_id' => 'nullable|exists:categories,id',
        ];

        // Additional rules for edit
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['name'] = 'sometimes|string|max:255';
            $rules['status'] = 'sometimes|string|in:approved,not_approved,canceled';
            $rules['client_id'] = 'prohibited'; // Prevent changing `client_id` on update
        }

        return $rules;
    }
}