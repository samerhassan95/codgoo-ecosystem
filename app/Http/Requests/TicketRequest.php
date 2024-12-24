<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketRequest extends FormRequest
{
    public function rules()
    {
        return [
            'reply' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|in:High,Medium,Low',
            'status' => 'nullable|in:pending,open, closed, answered',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
            // 'created_by' => 'required|exists:clients,id',
        ];
    }

    public function authorize()
    {
        return true;
    }
}


