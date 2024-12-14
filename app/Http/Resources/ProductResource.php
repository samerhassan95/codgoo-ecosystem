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
                'name' => $addon->addon->name,
                'price' => $addon->addon->price,
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
            'total_price' => $totalPrice,
            'attachments' => $this->attachments->map(function ($attachment) {
                return [
                    'file_path' => asset($attachment->file_path),
                ];
            }),
        ];
    }
}
