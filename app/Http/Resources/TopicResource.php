<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\Topic;


class TopicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'section_id'    => $this->section_id,
            'section_label' => $this->section_label,
            'header'        => $this->header,
            'description'   => $this->description,
            'date'          => $this->date,
            'created_at'    => $this->created_at,
             'steps' => $this->steps ?? [],
             'feedback' => [
                'yes_count' => $this->helpful_yes,
                'no_count'  => $this->helpful_no,
            ],
            // DESIGN: Related articles at the bottom of the screen
            'related_articles' => Topic::where('section_id', $this->section_id)
                ->where('id', '!=', $this->id)
                ->limit(3)
                ->get(['id', 'header'])
        
// 'galleries' => $this->galleries->map(function ($gallery) {
//     return url('storage/' . $gallery->image_path);
// }),
        ];
    }
}
