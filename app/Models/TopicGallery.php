<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopicGallery extends Model
{
    protected $fillable = ['topic_id', 'image_path'];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }
}
