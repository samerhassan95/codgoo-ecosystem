<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeetingRequest extends FormRequest
{
    public function rules()
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'slot_id' => 'required|exists:available_slots,id',
            'start_time' => 'required|date_format:H:i',
            'meeting_name' => 'required|string|max:255', 
            'project_id' => 'nullable|exists:projects,id',
        ];
    }
}

