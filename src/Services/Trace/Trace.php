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

    protected array $phases = [];

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

    public function decision(mixed $data = null): void
    {
        $this->records = array_filter($this->records, fn($record) => $record['type'] !== 'decision');

        $this->records[] = [
            'type' => 'decision',
            'timestamp' => microtime(true),
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
     * Get the parent trace.
     */
    public function parent(): ?Trace
    {
        return $this->parent;
    }

    public function phases(): array
    {
        return $this->phases;
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

    public function findPhases(\Closure $callback): array
    {
        $results = [];

        if ($callback($this)) {
            $results[] = $this;
        }

        foreach ($this->phases as $phase) {
            $results = array_merge(
                $results,
                $phase->findPhases($callback)
            );
        }

        return $results;
    }

    public function findRecords(\Closure $callback): array
    {
        $results = [];

        foreach ($this->records as $record) {
            if ($callback($record, $this)) {
                $results[] = $record;
            }
        }

        foreach ($this->phases as $phase) {
            $results = array_merge(
                $results,
                $phase->findRecords($callback)
            );
        }

        return $results;
    }

    public function hasPhases(): bool
    {
        return ! empty($this->phases);
    }

    protected function hasRecordType(string $type): bool
    {
        foreach ($this->records as $record) {
            if ($record['type'] === $type) {
                return true;
            }
        }

        return false;
    }

    public function hasRecords(): bool
    {
        if (! $this->hasPhases() && empty($this->records)) return false;

        if (! empty($this->records)) {
            return true;
        }

        foreach ($this->phases() as $phase) {
            if ($phase->hasRecords()) {
                return true;
            }
        }

        return false;
    }

    public function hasDecision(): bool
    {
        if ($this->hasRecordType('decision')) {
            return true;
        }

        foreach ($this->phases as $child) {
            if ($child->hasDecision()) {
                return true;
            }
        }

        return false;
    }

    public function hasException(): bool
    {
        if ($this->hasRecordType('exception')) {
            return true;
        }

        foreach ($this->phases as $child) {
            if ($child->hasException()) {
                return true;
            }
        }

        return false;
    }

    public function onlyDominantPath(): Trace
    {
        if (! $this->hasPhases()) return $this;

        $dominant = null;
        $max = 0;

        $clone = $this->cloneEmpty();

        foreach ($this->phases() as $phase) {
            $time = $phase->duration() ?? 0;

            if ($time > $max) {
                $max = $time;
                $dominant = $phase;
            }
        }

        if ($dominant) {
            $clone->addPhase($dominant->onlyDominantPath());
            return $clone;
        } else {
            return $this;
        }
    }

    public function onlyWithRecords(): ?Trace
    {
        if (! $this->hasRecords()) return null;

        $clone = $this->cloneEmpty();

        foreach ($this->phases() as $phase) {
            $child = $phase->onlyWithRecords();

            if ($child) {
                $clone->addPhase($child);
            }
        }

        return $clone;
    }

    public function onlyDecisionPath(): ?Trace
    {
        if (! $this->hasDecision()) return null;

        $clone = $this->cloneEmpty();

        if ($this->hasRecordType('decision') && ! $this->hasPhases()) {
            return $clone;
        }

        $decisions = [];

        foreach ($this->phases() as $phase) {
            if ($phase->hasDecision()) {
                $decisions[] = $phase;
            }
        }

        if (!empty($decisions)) {
            foreach ($decisions as $decision) {
                $clone->addPhase($decision->onlyDecisionPath());
            }

            return $clone;
        } else {
            return null;
        }
    }

    public function onlyFailedPath(): ?Trace
    {
        if (! $this->hasException()) return null;

        $clone = $this->cloneEmpty();

        if ($this->hasRecordType('exception') && ! $this->hasPhases()) {
            return $clone;
        }

        $failed = null;

        foreach ($this->phases() as $phase) {
            if ($phase->hasException()) {
                $failed = $phase;
                break;
            }
        }

        if ($failed) {
            $clone->addPhase($failed->onlyFailedPath());
            return $clone;
        } else {
            return null;
        }
    }

    public function mergeRecords(): Trace
    {
        $clone = $this->cloneEmpty();
        $records = $this->records();

        $merged = [
            'type' => 'merged',
            'timestamp' => microtime(true),
            'exception' => null,
            'data' => null,
        ];

        foreach ($records as $record) {
            if ($record['type'] === 'exception') {
                $merged['exception'] = $record['exception'];
            }

            if ($record['data'] !== null) {
                $merged['data'][] = $record['data'];
            }
        }

        $clone->records = [$merged];

        foreach ($this->phases() as $phase) {
            $clone->addPhase($phase->mergeRecords());
        }


        return $clone;
    }

    public function log(): void
    {
        if ($this->hasException()) {
            $trace = $this->onlyFailedPath();
        }

        if ($this->hasDecision()) {
            $trace = $this->onlyDecisionPath();
        }

        $trace = $this->onlyDominantPath();

        // log somehow
        // logger()->info(...);
    }

    public function cloneEmpty(): Trace
    {
        $clone = new Trace($this->name, null);
        $clone->id = $this->id;
        $clone->start = $this->start;
        $clone->end = $this->end;

        $clone->setRecords($this->records);

        return $clone;
    }

    public function records(): array
    {
        return $this->records;
    }

    public function setRecords(array $records): void
    {
        $this->records = $records;
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
    public function addPhase(Trace $phase): void
    {
        if ($phase->parent() !== $this) {
            $phase->setParent($this);
        }

        $this->phases[] = $phase;
    }

    public function flushPhases(): void
    {
        $this->phases = [];
    }

    public function flushRecords(): void
    {
        $this->records = [];
    }

    public function toTree(): array
    {
        $tree = [];

        foreach ($this->phases() as $phase) {
            $tree[$this->name()][] = $phase->toTree();
        }

        if (!$this->hasPhases() && $this->parent() !== null) {
            $tree[$this->name()] = [];
        }

        return $tree;
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
            'phases' => array_map(fn(Trace $phase) => $phase->toArray(), $this->phases),
        ];
    }
}
