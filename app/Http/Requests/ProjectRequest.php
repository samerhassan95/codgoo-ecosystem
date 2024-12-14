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
                    if ($this->input('created_by_type') !== 'Admin' && $value !== null) {
                        $fail('Only Admin can set the price.');
                    }
                },
            ],
            'note' => 'nullable|string|max:1000',
            'status' => 'string|in:approved,not_approved,canceled',
            'created_by_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $type = $this->input('created_by_type');
                    if ($type === 'Admin' && !\App\Models\Admin::where('id', $value)->exists()) {
                        $fail('The selected admin does not exist.');
                    } elseif ($type === 'Client' && !\App\Models\Client::where('id', $value)->exists()) {
                        $fail('The selected client does not exist.');
                    }
                },
            ],
            'created_by_type' => 'required|string|in:Admin,Client',
        ];

        // Additional rules for edit
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['name'] = 'sometimes|string|max:255';
            $rules['status'] = 'sometimes|string|in:approved,not_approved,canceled';
            $rules['created_by_id'] = 'prohibited'; // Prevent changing `created_by_id` on update
            $rules['created_by_type'] = 'prohibited'; // Prevent changing `created_by_type` on update
        }

        return $rules;
    }
}
