<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectAddonRequest extends FormRequest
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'addon_id' => 'required|exists:addons,id',
        ];
    }
}
