<?php

namespace Rosalana\Core\Services\Trace;

class Span
{
    public string $name;
    public float $startedAt;
    public ?float $endedAt = null;

    public ?Span $parent = null;
    public array $children = [];
    public array $records = [];

    public function __construct(string $name, ?Span $parent = null)
    {
        $this->name = $name;
        $this->parent = $parent;
        $this->startedAt = microtime(true);
    }

    public function end(): void
    {
        $this->endedAt = microtime(true);
    }

    public function duration(): ?float
    {
        return $this->endedAt
            ? ($this->endedAt - $this->startedAt) * 1000
            : null;
    }

    public function record(mixed $data): void
    {
        $this->records[] = [
            'time' => microtime(true),
            'data' => $data,
        ];
    }
}
