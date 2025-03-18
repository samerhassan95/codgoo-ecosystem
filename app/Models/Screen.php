<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Screen extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'task_id',
        'dev_mode',
        'implemented',
        'integrated',
        'screen_code',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
