<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Client extends Authenticatable implements JWTSubject
{
    use HasFactory, SoftDeletes;

    protected $table = 'clients';

    protected $guarded = [];

    public function getJWTIdentifier()
    {
        return (string) $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'type' => 'client',
            'role' => $this->role ?? 'client',
        ];
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'uploadedBy');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    public function ticketReplies()
    {
        return $this->morphMany(TicketReply::class, 'creator');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function taskDiscussions(): MorphMany
    {
        return $this->morphMany(TaskDiscussion::class, 'createdBy');
    }

    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Subscription::class, 'client_id');
    }

    public function getActiveAppScopes(): array
    {
        $activeApps = $this->subscriptions()
            ->active()
            ->pluck('app_name')
            ->unique()
            ->toArray();

        $scopes = [];

        foreach ($activeApps as $app) {
            $scopes[] = "app:{$app}:read";
            $scopes[] = "app:{$app}:write";
        }

        return $scopes;
    }
}
