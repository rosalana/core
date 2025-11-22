<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;
use Rosalana\Core\Contracts\Configure\Node;

class Comment implements Node
{
    public static function parse(array $content): Collection
    {
        return collect();
    }

    public function render(): array
    {
        return [];
    }

    public function startLine(): int
    {
        return 0;
    }

    public function endLine(): int
    {
        return 0;
    }

    public function raw(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return [];
    }
}
