<?php

namespace Rosalana\Core\Services\Trace;

class Span
{
    public string $name;
    public float $start;
    public ?float $end = null;

    public ?Span $parent = null;
    public array $children = [];
    public array $records = [];

    public function __construct(?string $name, ?Span $parent = null)
    {
        $this->name = $name ?? 'anonymous';
        $this->parent = $parent;
    }

    public function start(): void
    {
        $this->start = microtime(true);
    }

    public function finish(): void
    {
        $this->end = microtime(true);
    }

    public function record(mixed $data = null): void
    {
        $this->records[] = [
            'type' => 'record',
            'time' => microtime(true),
            'data' => $data,
        ];
    }

    public function fail(\Throwable $exception, mixed $data = null): void
    {
        $this->records[] = [
            'type' => 'exception',
            'time' => microtime(true),
            'exception' => $exception,
            'data' => $data,
        ];
    }

    public function duration(): ?float
    {
        return $this->end
            ? ($this->end - $this->start) * 1000
            : null;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'start' => $this->start,
            'end' => $this->end,
            'duration' => $this->duration(),
            'records' => $this->records,
            'children' => array_map(fn(Span $child) => $child->toArray(), $this->children),
        ];
    }
}
