<?php

namespace Rosalana\Core\Services\Trace;

use Illuminate\Support\Str;

class Trace
{
    protected string $id;

    protected string $name;

    protected ?float $start = null;

    protected ?float $end = null;

    protected ?Trace $parent = null;

    protected array $children = [];

    protected array $records = [];

    public function __construct(?string $name, ?Trace $parent = null)
    {
        $this->name = $name ?? 'anonymous';
        $this->parent = $parent;
        $this->id = (string) Str::uuid();
    }

    /**
     * Start the trace.
     */
    public function start(): void
    {
        $this->start = microtime(true);
    }

    /**
     * Finish the trace if not already finished.
     */
    public function finish(): void
    {
        if ($this->end !== null) return;

        $this->end = microtime(true);
    }

    /**
     * Record a data point in the trace.
     */
    public function record(mixed $data = null): void
    {
        $this->records[] = [
            'type' => 'record',
            'timestamp' => microtime(true),
            'data' => $data,
        ];
    }

    /**
     * Record an exception in the trace.
     */
    public function fail(\Throwable $exception, mixed $data = null): void
    {
        $this->records[] = [
            'type' => 'exception',
            'timestamp' => microtime(true),
            'exception' => $exception,
            'data' => $data,
        ];
    }

    /**
     * Get the trace ID.
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get the trace name.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Set the parent trace.
     */
    public function setParent(?Trace $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Add a child trace.
     */
    public function addChild(Trace $child): void
    {
        $this->children[] = $child;
    }

    /**
     * Get the duration of the trace in milliseconds.
     */
    public function duration(): ?float
    {
        return $this->end
            ? ($this->end - $this->start) * 1000
            : null;
    }

    /**
     * Convert the trace to an array representation.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'duration' => $this->duration(),
            'records' => $this->records,
            'phases' => array_map(fn(Trace $child) => $child->toArray(), $this->children),
        ];
    }
}
