<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImplementedApi extends Model
{
    use HasFactory;

    protected $fillable = [
        'requested_api_id',
        'postman_collection_url',
        'status',
    ];

    public function requestedApi()
    {
        return $this->belongsTo(RequestedApi::class);
    }
    
}
