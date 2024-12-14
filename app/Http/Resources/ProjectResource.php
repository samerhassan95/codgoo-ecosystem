<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request)
    {
        $addons = $this->addons->map(function ($addon) {
            return [
                'id' => $addon->id,
                'name' => $addon->addon->name,
                'price' => $addon->addon->price,
            ];
        });

        $totalPrice = $this->price + $addons->sum('price');

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'note' => $this->note,
            'status' => $this->status,
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
