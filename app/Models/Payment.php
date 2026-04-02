<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
   protected $fillable = [
        'payable_type',
        'payable_id',
        'payer_id',
        'payer_type',
        'provider',
        'provider_payment_id',
        'amount',
        'currency',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function payable()
    {
        return $this->morphTo();
    }
}
