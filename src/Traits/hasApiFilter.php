<?php

namespace Rosalana\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Rosalana\Core\Http\Api\ApiFilter;

/**
 * @method static Builder filter(ApiFilter $filters)
 */
trait hasApiFilter
{
    /**
     * Apply the given filters to the query builder.
     * @param Builder $builder
     * @param ApiFilter $filters
     * @return Builder
     */
    public function scopeFilter(Builder $builder, ApiFilter $filters): Builder
    {
        return $filters->apply($builder);
    }
}
