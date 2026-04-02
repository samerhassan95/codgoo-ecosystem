<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountShare extends Model
{
    protected $table = 'account_shares';

    // Only allow these attributes to be mass assigned
    protected $fillable = [
        'client_id',
        'email',
        'apps',
        'status',
        'invite_code',
    ];

    // Cast apps JSON/text to array automatically
    protected $casts = [
        'apps' => 'array',
    ];

    // If you prefer to hide any fields from JSON responses, add them here
    // protected $hidden = ['invite_code'];

    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class);
    }
}