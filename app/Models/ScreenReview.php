<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreenReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'screen_id',
        'comment',
    ];

    public function screen()
    {
        return $this->belongsTo(Screen::class);
    }
}
