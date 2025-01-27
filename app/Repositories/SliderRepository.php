<?php

namespace App\Repositories;

use App\Models\Slider;

class SliderRepository implements SliderRepositoryInterface
{
    public function all()
    {
        return Slider::with('product')->get();
    }

    public function create(array $data)
    {
        return Slider::create($data);
    }

    public function findById($id)
    {
        return Slider::with('product')->findOrFail($id);
    }

    public function delete($id)
    {
        return Slider::destroy($id);
    }
}
