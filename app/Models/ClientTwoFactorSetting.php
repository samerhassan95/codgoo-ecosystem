<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ClientTwoFactorSetting extends Model
{
    protected $table = 'client_two_factor_settings';
    protected $guarded = [];
    protected $casts = ['enabled' => 'boolean'];
}