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

    public function depth(): array
    {
        $depths = [];
        foreach ($this->raw as $lineNumber => $line) {
            $trimmed = ltrim($line);
            $depths[$lineNumber] = strlen($line) - strlen($trimmed);
        }
        return $depths;
    }

    public function toArray(): array
    {
        return [
            'type' => class_basename($this),
            'start' => $this->start,
            'end' => $this->end,
            'raw' => $this->raw,
            'depth' => $this->depth(),
        ];
    }
}
