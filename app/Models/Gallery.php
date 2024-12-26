<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    protected $fillable = ['galleriable_id', 'galleriable_type', 'file_path'];

    public function galleriable()
    {
        return $this->morphTo();
    }
}

