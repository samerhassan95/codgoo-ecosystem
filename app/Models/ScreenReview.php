<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'screen_id',
        'comment',
        'review_type',
        'created_by',
    ];

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function creator()
    {
        return $this->morphTo();
    }
    
}
