<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Translatable\HasTranslations;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Client extends Authenticatable implements JWTSubject
{
    use HasFactory,SoftDeletes,HasApiTokens;
    protected $table = 'clients';

    protected $guarded = [];


    public function getJWTIdentifier()
    {
        return (string) $this->getKey();  // Ensure it returns a string, typically the user ID
    }
    

    public function getJWTCustomClaims()
    {
        return [
            'type' => 'client',
        ];
    }
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'uploadedBy');
    }
    public function projects()
{
    return $this->morphMany(Project::class, 'creator', 'created_by_type', 'created_by_id');
}


    public function ticketReplies()
    {
        return $this->morphMany(TicketReply::class, 'creator');
    }

    public function notifications()
{
    return $this->morphMany(Notification::class, 'notifiable');
}

}
