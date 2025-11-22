<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;
use Rosalana\Core\Contracts\Configure\Node as NodeInterface;

abstract class Node implements NodeInterface
{
    public function __construct(
        protected int $start,
        protected int $end,
        protected array $raw,
    ) {}

    abstract public static function parse(array $content): Collection;

    abstract public function render(): array;

    public function startLine(): int
    {
        return $this->start;
    }

    public function endLine(): int
    {
        return $this->end;
    }

    public function raw(): array
    {
        return $this->raw;
    }

    public function depth(): int
    {
        $depth = 0;
        foreach ($this->raw as $line) {
            $trimmed = ltrim($line);
            if (strlen($line) !== strlen($trimmed)) {
                $depth = strlen($line) - strlen($trimmed);
                break;
            }
        }
        return (int) ($depth / 4);
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start,
            'end' => $this->end,
            'raw' => $this->raw,
        ];
    }
}
