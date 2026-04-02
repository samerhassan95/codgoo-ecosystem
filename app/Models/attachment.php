<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = ['file_path', 'uploaded_by_id', 'uploaded_by_type'];

    public function uploadedBy()
    {
        return $this->morphTo();
    }

    public function attachable()
    {
        return $this->morphTo();
    }

}
