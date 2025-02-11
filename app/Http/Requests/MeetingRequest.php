<?php

namespace App\Http\Requests;

use App\Models\AvailableSlot;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class MeetingRequest extends FormRequest
{
    public function rules()
{
    return [
        'client_id' => 'required|exists:clients,id',
        'slot_id' => ['required', 'exists:available_slots,id', function ($attribute, $value, $fail) {
            // جلب تاريخ الـ Slot من قاعدة البيانات
            $slotDate = \DB::table('available_slots')->where('id', $value)->value('date');

            if (!$slotDate) {
                $fail('Slot غير موجود أو التاريخ غير متاح.');
                return;
            }

            // حساب التاريخ المسموح (بعد يومين من اليوم الحالي)
            $minDate = now()->addDays(2)->toDateString();

            // التحقق من أن تاريخ الـ Slot بعد يومين على الأقل
            if ($slotDate < $minDate) {
                $fail('لا يمكنك حجز هذا الـ Slot، يجب أن يكون الحجز بعد يومين على الأقل.');
            }
        }],
        'start_time' => 'required|date_format:H:i',
        'meeting_name' => 'required|string|max:255', 
        'project_id' => 'nullable|exists:projects,id',
        'description' => 'nullable|string|max:1000',
    ];
}

}
