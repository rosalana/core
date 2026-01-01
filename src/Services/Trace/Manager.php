<?php

namespace Rosalana\Core\Services\Trace;

class Manager
{
    /** @var Span[] */
    protected array $stack = [];

    /** @var Span[] */
    protected array $roots = [];

    public function start(?string $name = null): Span
    {
        $parent = $this->current();

        $span = new Span($name, $parent);
        $span->start();

        if ($parent) {
            $parent->children[] = $span;
        } else {
            $this->roots[] = $span;
        }

        $this->stack[] = $span;

        return $span;
    }

    public function end(): ?Span
    {
        $span = array_pop($this->stack);

        if ($span) {
            $span->finish();
        }

        return $span;
    }

    public function finish(): ?Span
    {
        if ($this->current()) {
            $this->end();
        }

        return $this->roots ? array_shift($this->roots) : null;
    }

    public function wrap(callable $process, ?string $name = null): mixed
    {
        $span = $this->start($name);

        try {
            return $process();
        } catch (\Throwable $e) {
            $span->fail($e);
            throw $e;
        } finally {
            $this->end();
        }
    }

    public function record(mixed $data): void
    {
        if ($span = $this->current()) {
            $span->record($data);
        }
    }

    public function exception(\Throwable $exception, mixed $data = null): void
    {
        if ($span = $this->current()) {
            $span->fail($exception, $data);
        }
    }

    public function phase(string $name): Span
    {
        if ($this->current()) {
            $this->end();
        }

        return $this->start($name);
    }

    protected function current(): ?Span
    {
        return end($this->stack) ?: null;
    }

    public function getTraces(): array
    {
        return $this->roots;
    }

    public function flush(): void
    {
        $this->stack = [];
        $this->roots = [];
    }
}
