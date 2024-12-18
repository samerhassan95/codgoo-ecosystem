<?php

namespace App\Repositories;

interface SliderRepositoryInterface
{
    public function all();
    public function create(array $data);
    public function findById($id);
    public function delete($id);
}
