<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestedApi extends Model
{
    use HasFactory;

    protected $fillable = [
        'screen_id',
        'endpoint',
        'method',
        'request_body',
        'response_structure',
    ];

    protected $casts = [
        'request_body' => 'array',
        'response_structure' => 'array',
    ];

    public function screen()
    {
        return $this->belongsTo(Screen::class);
    }
    public function implementedApis()
    {
        return $this->hasMany(ImplementedApi::class);
    }

}
