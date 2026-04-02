<?php

namespace App\Models;
use App\Models\BusinessAppSubscription;
use App\Models\BusinessAppPlan;
use App\Models\BusinessApp;
use App\Models\BusinessAppSubscriptionPayment;
use App\Models\Client;

use Illuminate\Database\Eloquent\Model;

class BusinessAppSubscription extends Model
{
        protected $fillable = [
        'customer_id',
        'service_app_id',
        'business_app_plan_id',
        'status',
        'is_approved',
        'started_at',
        'expires_at',
        'approved_at',
        'approved_by'
    ];

    public function app()
    {
        return $this->belongsTo(ServiceApp::class,'service_app_id');
    }

    public function plan()
    {
        return $this->belongsTo(BusinessAppPlan::class,'business_app_plan_id');
    }

    public function payment()
    {
        return $this->hasOne(BusinessAppSubscriptionPayment::class);
    }
        public function client()
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }
}
