<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request)
    {
        // Calculate completion percentage
        $totalMilestones = $this->milestones->count();
        $completedMilestones = $this->milestones->where('status', 'completed')->count();

        $completionPercentage = $totalMilestones > 0 ? ($completedMilestones / $totalMilestones) * 100 : 0;

        // Determine project status based on milestones
        $projectStatus = 'pending'; // Default to pending if no milestones exist
        if ($totalMilestones > 0) {
            if ($this->milestones->every(fn ($milestone) => $milestone->status === 'completed')) {
                $projectStatus = 'completed';
            } elseif ($this->milestones->contains(fn ($milestone) => in_array($milestone->status, ['in_progress', 'not_started']))) {
                $projectStatus = 'ongoing';
            }
        }

        // Prepare addons data
        $addons = $this->addons->map(function ($addon) {
            return [
                'id' => $addon->id,
                'name' => $addon->name,
                'price' => $addon->price,
            ];
        });

        // Calculate total price including addons
        $totalPrice = $this->price + $addons->sum('price');

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'note' => $this->note,
            'status' => $projectStatus,  // Project status based on milestones
            'completion_percentage' => $completionPercentage, // Completion percentage
            'created_by_id' => $this->created_by_id,
            'created_by_type' => $this->created_by_type,
            'addons' => $addons,
            'total_price' => $totalPrice,
            'attachments' => $this->attachments->map(function ($attachment) {
                return [
                    'file_path' => asset($attachment->file_path),
                ];
            }),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

