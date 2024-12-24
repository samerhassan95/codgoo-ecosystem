<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $departmentId = $this->route('department'); // Get the current department ID from the route
    
        return [
            'name' => "required|string|max:255|unique:departments,name,{$departmentId}", // Exclude current ID
        ];
    }
    

}
