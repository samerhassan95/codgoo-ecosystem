<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ClientEmail extends Model
{
    protected $table = 'client_emails';
    protected $guarded = [];
    protected $casts = ['verified' => 'boolean'];
}