<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'description',
        'date_from',
        'date_to',
        'status',
        'holiday_request_type_id',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function holidayRequestType(): BelongsTo
    {
        return $this->belongsTo(HolidayRequestType::class);
    }
}

