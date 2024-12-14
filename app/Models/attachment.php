<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class attachment extends Model
{
    protected $fillable = ['file_path'];

    public function attachable()
    {
        return $this->morphTo();
    }
}
