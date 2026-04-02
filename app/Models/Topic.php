<?php

namespace App\Models;

use App\Enum\SectionEnum;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $fillable = ['section_id', 'header', 'description', 'date','steps'];


    protected $casts = [
        'steps' => 'array', // Cast JSON to array
    ];

    public function getSectionLabelAttribute(): ?string
    {
        return SectionEnum::getLabel($this->section_id);
    }

    // Polymorphic relationship
    // public function galleries()
    // {
    //     return $this->morphMany(Gallery::class, 'galleriable');
    // }
}

