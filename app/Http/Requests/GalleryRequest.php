<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GalleryRequest extends FormRequest
{
    public function authorize()
    {
        return true; // You can set this to true for now or implement user authorization logic
    }

    public function rules()
{
    return [
        'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'galleriable_id' => 'required|integer',
        'galleriable_type' => 'required|in:employee,topic',
    ];
}

public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $galleriableType = $this->input('galleriable_type');
        $galleriableId = $this->input('galleriable_id');
        
        if ($galleriableType == 'employee') {
            $exists = \App\Models\Employee::where('id', $galleriableId)->exists();
        } elseif ($galleriableType == 'topic') {
            $exists = \App\Models\Topic::where('id', $galleriableId)->exists();
        } else {
            $exists = false;
        }

        if (!$exists) {
            $validator->errors()->add('galleriable_id', 'The galleriable_id does not exist for the given galleriable_type.');
        }
    });
}

}
