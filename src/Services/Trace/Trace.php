<?php

namespace Rosalana\Core\Services\Trace;

use Illuminate\Support\Str;
use Rosalana\Core\Services\Trace\Rendering\Registry;

class Trace
{
    protected string $id;

    protected string $name;

    protected ?float $start = null;

    protected ?float $end = null;

    protected ?Trace $parent = null;

    /** @var Trace[] */
    protected array $phases = [];

    /** @var array[] */
    protected array $records = [];

    public function __construct(?string $name, ?Trace $parent = null)
    {
        $this->name = $name ?? 'anonymous';
        $this->parent = $parent;
        $this->id = (string) Str::uuid();
    }

    /**
     * Start the trace.
     * 
     * @return void
     */
    public function start(): void
    {
        $this->start = microtime(true);
    }

    /**
     * Finish the trace if not already finished.
     * 
     * @return void
     */
    public function finish(): void
    {
        if ($this->end !== null) return;

        $this->end = microtime(true);
    }

    /**
     * Record a data point in the trace.
     * 
     * @param mixed $data
     * @return void
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
     * 
     * @param \Throwable $exception
     * @param mixed $data
     * @return void
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
     * Record a decision in the trace.
     * 
     * @param mixed $data
     * @return void
     */
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
     * 
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get the trace name.
     * 
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the parent trace.
     * 
     * @return Trace|null
     */
    public function parent(): ?Trace
    {
        return $this->parent;
    }

    /**
     * Get the child phases.
     * 
     * @return Trace[]
     */
    public function phases(): array
    {
        return $this->phases;
    }

    /**
     * Get the duration of the trace in milliseconds.
     * 
     * @return float|null
     */
    public function duration(): ?float
    {
        return $this->end
            ? ($this->end - $this->start) * 1000
            : null;
    }

    /**
     * Get the start time of the trace.
     * 
     * @return float|null
     */
    public function startTime(): ?float
    {
        return $this->start;
    }

    /**
     * Get the end time of the trace.
     * 
     * @return float|null
     */
    public function endTime(): ?float
    {
        return $this->end;
    }

    /**
     * Get a record by its type.
     * 
     * @param string $type
     * @return array|null
     */
    public function getRecordByType(string $type): ?array
    {
        foreach ($this->records as $record) {
            if ($record['type'] === $type) {
                return $record;
            }
        }

        return null;
    }

    /**
     * Get the decision record.
     * 
     * @return array|null
     */
    public function getDecision(): ?array
    {
        return $this->getRecordByType('decision');
    }

    /**
     * Get the exception record.
     * 
     * @return array|null
     */
    public function getException(): ?array
    {
        return $this->getRecordByType('exception');
    }

    /**
     * Find phases matching the given callback.
     * 
     * @return Trace[]
     */
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

    /**
     * Find records matching the given callback.
     * 
     * @return array[]
     */
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

    /**
     * Check if the trace has child phases.
     * 
     * @return bool
     */
    public function hasPhases(): bool
    {
        return ! empty($this->phases);
    }

    /**
     * Check if the trace has a record of the given type.
     * 
     * @param string $type
     * @return bool
     */
    public function hasRecordType(string $type): bool
    {
        foreach ($this->records as $record) {
            if ($record['type'] === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the trace or its phases have any records.
     * 
     * @return bool
     */
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

    /**
     * Check if the trace or its phases have a decision record.
     * 
     * @return bool
     */
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

    /**
     * Check if the trace or its phases have an exception record.
     * 
     * @return bool
     */
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

    /**
     * Get the dominant path (the phase with the longest duration).
     * 
     * @return Trace
     */
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

    /**
     * Get only phases that have records.
     * 
     * @return Trace|null
     */
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

    /**
     * Get only phases that have decision records.
     * 
     * @return Trace|null
     */
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

    /**
     * Get only phases that have exception records.
     * 
     * @return Trace|null
     */
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

    /**
     * Get only decision records.
     * 
     * @return array[]
     */
    public function onlyDecisionRecords(): array
    {
        return $this->findRecords(function ($record) {
            return $record['type'] === 'decision';
        });
    }

    /**
     * Get only exception records.
     * 
     * @return array[]
     */
    public function onlyExceptionRecords(): array
    {
        return $this->findRecords(function ($record) {
            return $record['type'] === 'exception';
        });
    }

    /**
     * Get only data records.
     * 
     * @return array[]
     */
    public function onlyDataRecords(): array
    {
        return $this->findRecords(function ($record) {
            return $record['type'] === 'record';
        });
    }

    /**
     * Merge all records into a single trace.
     * 
     * @return Trace
     */
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

    /**
     * Log the trace using the specified renderer.
     * 
     * @param null|string|class-string<\Rosalana\Core\Services\Trace\Rendering\Target> $target
     * @return void
     */
    public function log(?string $target = null): void
    {
        $target ??= 'file';

        Registry::render($target, $this);
    }

    /**
     * Clone the trace without phases and records.
     * 
     * @return Trace
     */
    public function cloneEmpty(): Trace
    {
        $clone = new Trace($this->name, null);
        $clone->id = $this->id;
        $clone->start = $this->start;
        $clone->end = $this->end;

        $clone->setRecords($this->records);

        return $clone;
    }

    /**
     * Get all records.
     * 
     * @return array[]
     */
    public function records(): array
    {
        return $this->records;
    }

    /**
     * Set the records.
     * 
     * @param array[] $records
     * @return void
     */
    public function setRecords(array $records): void
    {
        $this->records = $records;
    }

    /**
     * Set the parent trace.
     * 
     * @param Trace|null $parent
     * @return void
     */
    public function setParent(?Trace $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Add a child trace.
     * 
     * @param Trace $phase
     * @return void
     */
    public function addPhase(Trace $phase): void
    {
        if ($phase->parent() !== $this) {
            $phase->setParent($this);
        }

        $this->phases[] = $phase;
    }

    /**
     * Flush all child phases.
     * 
     * @return void
     */
    public function flushPhases(): void
    {
        $this->phases = [];
    }

    /**
     * Flush all records.
     * 
     * @return void
     */
    public function flushRecords(): void
    {
        $this->records = [];
    }

    /**
     * Convert the trace to a tree representation.
     * 
     * @return array
     */
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
     * 
     * @return array
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
