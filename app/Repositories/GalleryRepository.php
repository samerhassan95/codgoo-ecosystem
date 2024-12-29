<?php

namespace App\Repositories;

use App\Models\Gallery;
use App\Repositories\GalleryRepositoryInterface;

class GalleryRepository implements GalleryRepositoryInterface
{
    public function store(array $data)
    {
        return Gallery::create($data);
    }
}
