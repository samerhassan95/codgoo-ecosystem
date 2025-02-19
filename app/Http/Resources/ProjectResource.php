<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request)
    {
        $totalMilestones = $this->milestones ? $this->milestones->count() : 0;
        $completedMilestones = $this->milestones ? $this->milestones->where('status', 'completed')->count() : 0;
        

        $completionPercentage = $totalMilestones > 0 ? ($completedMilestones / $totalMilestones) * 100 : 0;

        $projectStatus = 'pending'; 
        if ($totalMilestones > 0) {
            if ($this->milestones->every(fn ($milestone) => $milestone->status === 'completed')) {
                $projectStatus = 'completed';
            } elseif ($this->milestones->contains(fn ($milestone) => in_array($milestone->status, ['in_progress', 'not_started']))) {
                $projectStatus = 'ongoing';
            }
        }

        $addons = $this->addons->map(function ($addon) {
            return [
                'id' => $addon->id,
                'name' => $addon->name,
                'price' => $addon->price,
            ];
        });

        $totalPrice = ($this->product ? $this->product->price : 0) + $addons->sum('price');

        return
        [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'note' => $this->note,
            'status' => $projectStatus,  
            'completion_percentage' => $completionPercentage, 
            'created_by_id' => $this->created_by_id,
            'created_by_type' => $this->created_by_type,
            'addons' => $addons,
            'total_price' => $totalPrice,
            'category' => $this->category,
            'attachments' => $this->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'file_path' => asset($attachment->file_path),
                ];
            }),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

