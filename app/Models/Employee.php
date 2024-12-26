<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Translatable\HasTranslations;
use Illuminate\Foundation\Auth\User as Authenticatable;
class Employee extends Authenticatable implements JWTSubject
{
    use HasFactory,SoftDeletes,HasApiTokens;

    protected $fillable = ['name', 'phone', 'email', 'image', 'cover_photo', 'intro','password'];

    public function galleries()
    {
        return $this->morphMany(Gallery::class, 'galleriable');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'employee_skill', 'employee_id', 'skill_id');
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
}

