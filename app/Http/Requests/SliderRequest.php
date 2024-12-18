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
            'name' => 'required|string|unique:sliders,name|max:255',
            'products' => 'required|array', 
            'products.*.id' => 'required|exists:products,id',
            'products.*.image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
