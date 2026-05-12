<?php

namespace Rosalana\Core\Services\Basecamp;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    public array $meta = [];

    public function withMeta(array $meta): static
    {
        $this->meta = $meta;

        return $this;
    }
}
