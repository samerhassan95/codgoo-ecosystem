<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketReplyRequest extends FormRequest
{
    public function rules()
    {
        return [
            'reply' => 'required|string|max:255',
            'ticket_id' => 'required|exists:tickets,id',
            'admin_id' => 'required|exists:admins,id',
        ];
    }

    public function authorize()
    {
        return true;
    }
}


