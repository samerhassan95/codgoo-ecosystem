<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SkillRequest  extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    
        if ($this->isMethod('post')) {
            $rules['name'] = 'required|string|unique:skills';
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['name'] = 'sometimes|string|unique:skills,name,' . $this->skill; 
        }
    
        return $rules;
    }
    
}
