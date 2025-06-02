<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtendTaskTimeRequest extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'employee_id',
        'task_id',
        'new_deadline',
        'reason',
    ];

    protected $casts = [
        'new_deadline' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
