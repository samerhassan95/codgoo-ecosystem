<?php

namespace App\Http\Bases\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class FilterTaskLabel implements Filter
{
    
    public function __invoke(Builder $query, $value, string $property)
    {
        $query->where('label', 'like', "%{$value}%");
    }
}
