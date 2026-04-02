<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ClientPaymentMethod extends Model
{
    protected $table = 'client_payment_methods';
    protected $guarded = [];
    protected $casts = ['default' => 'boolean'];
}