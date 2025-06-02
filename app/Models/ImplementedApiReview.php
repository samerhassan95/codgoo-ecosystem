<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImplementedApiReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'implemented_api_id',
        'review',
        'creator_id',
        'creator_type',
    ];

    public function implementedApi()
    {
        return $this->belongsTo(ImplementedApi::class);
    }

    public function creator()
    {
        return $this->morphTo();
    }
}
