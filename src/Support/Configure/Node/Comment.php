<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

class Comment extends Node
{
    public static function parse(array $content): Collection
    {
        return collect();
    }

    public function render(): array
    {
        return [];
    }
}
