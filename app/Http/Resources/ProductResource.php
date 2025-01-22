<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        $addons = $this->addons->map(function ($addon) {
            return [
                'id' => $addon->id,
                'name' => $addon->name,
                'price' => $addon->price,
                'icon' => $addon->icon ? asset( $addon->icon) : null,
                'description' => $addon->description,
            ];
        });

        $totalPrice = $this->price + $addons->sum('price');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'note' => $this->note,
            'addons' => $addons,
            'image' => $this->image ? asset('storage/' . $this->image) : null,  // Include image URL
            'total_price' => $totalPrice,
            'attachments' => $this->attachments->map(function ($attachment) {
                return [
                    'file_path' => asset($attachment->file_path),
                ];
            }),
        ];
    }
}
