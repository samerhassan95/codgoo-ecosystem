<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
{
    $rules = [
        'name' => 'required|string|unique:categories,name',
        'visible' => 'nullable|boolean',
    ];

    if ($this->isMethod('put') || $this->isMethod('patch')) {
        // Correct way to access the 'category' route parameter
        $categoryId = $this->route('category');  // Get category ID from route

        // Update the validation rule to exclude the category being updated
        $rules['name'] = 'sometimes|string|unique:categories,name,' . $categoryId;
    }

    return $rules;
}

}
