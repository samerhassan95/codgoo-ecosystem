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
        'created_by',
    ];

    public function implementedApi()
    {
        return $this->belongsTo(ImplementedApi::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
