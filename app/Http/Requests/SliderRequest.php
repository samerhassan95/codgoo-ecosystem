<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SliderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'product_id' => 'nullable|exists:products,id',
            'image'      => 'required|array', 
        // Validate each item inside the array is a file
        'image.*'    => 'image|max:10240',
        
        ];
    }
}
