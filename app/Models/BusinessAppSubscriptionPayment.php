<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessAppSubscriptionPayment extends Model
{
        protected $fillable = [
        'business_app_subscription_id',
        'attachment_url',
        'status'
    ];

    public function subscription()
    {
        return $this->belongsTo(BusinessAppSubscription::class);
    }
}
