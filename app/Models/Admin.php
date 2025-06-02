<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 
use Spatie\Translatable\HasTranslations; 
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\MorphMany;


class Admin extends Authenticatable implements JWTSubject
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable;

    protected $guarded = [];
    protected $table = 'admins';

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'type' => 'admin',
        ];
    }
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'uploadedBy');
    }

    
    // public function projects()
    // {
    //     return $this->morphMany(Project::class, 'created_by');
    // }
    
    
    public function taskDiscussions(): MorphMany
    {
        return $this->morphMany(TaskDiscussion::class, 'createdBy');
    }

    
    public function ticketReplies()
    {
        return $this->morphMany(TicketReply::class, 'creator');
    }
}
