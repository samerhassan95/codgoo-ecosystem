<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectBanner extends Model
{
       protected $fillable = ['project_id', 'image_path', 'caption', 'order'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
