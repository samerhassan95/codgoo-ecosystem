<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = ['project_id', 'admin_id', 'file_path', 'status', 'signed_at'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
