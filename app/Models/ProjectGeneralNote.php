<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectGeneralNote extends Model
{
    
    protected $fillable = [
        'project_id',
        'note',
        'created_by',
        'updated_by',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
