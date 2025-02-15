<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'start_time' => ['required', 'date_format:H:i'],
            'meeting_name' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'client_id' => ['required', 'exists:clients,id'],
            'slot_id' => [
                'required',
                'exists:available_slots,id',
                function ($attribute, $value, $fail) {
                    $slotDate = DB::table('available_slots')->where('id', $value)->value('date');

                    if (!$slotDate) {
                        $fail('Slot غير موجود أو التاريخ غير متاح.');
                        return;
                    }

                    $minDate = now()->addDays(2)->toDateString();

                    if ($slotDate < $minDate) {
                        $fail('لا يمكنك حجز هذا الـ Slot، يجب أن يكون الحجز بعد يومين على الأقل.');
                    }
                }
            ],
        ];
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            foreach ($rules as $key => $rule) {
                if (is_array($rule)) {
                    // Separate closures from string-based validation rules
                    $closures = array_filter($rule, fn($item) => $item instanceof \Closure);
                    $nonClosures = array_filter($rule, fn($item) => !$item instanceof \Closure);
        
                    // Remove 'required' from non-closure rules
                    $nonClosures = array_diff($nonClosures, ['required']);
        
                    // Merge closures back
                    $rules[$key] = array_merge(['nullable'], $nonClosures, $closures);
                } else {
                    $rules[$key] = str_replace('required|', '', $rule);
                    $rules[$key] = 'nullable|' . $rules[$key];
                }
            }
        }
        

        return $rules;
    }
}
