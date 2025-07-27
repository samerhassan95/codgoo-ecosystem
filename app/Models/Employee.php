<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Translatable\HasTranslations;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Authenticatable implements JWTSubject
{
    use HasFactory,SoftDeletes,HasApiTokens;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'image',
        'cover_photo',
        'intro',
        'password',
        'experience_years',
        'graduation_year',
        'birth_date',
        'role',
        'join_date',
        'device_token',
    ];

    public function galleries()
    {
        return $this->morphMany(Gallery::class, 'galleriable');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'employee_skill', 'employee_id', 'skill_id');
    }
public function isAdmin()
{
    return false;
}


    public function getJWTIdentifier()
    {
        return (string) $this->getKey();  
    }
    

    public function getJWTCustomClaims()
    {
        return [
            'type' => 'employee',
        ];
    }

    public function taskDiscussions(): MorphMany
    {
        return $this->morphMany(TaskDiscussion::class, 'createdBy');
    }

    public function address(): HasOne
    {
        return $this->hasOne(Address::class, 'employee_id');
    }
    
    public function taskAssignments()
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_assignments')
                    ->withPivot(['status', 'estimated_hours', 'header'])
                    ->withTimestamps();
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

}

