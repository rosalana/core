<?php

namespace Rosalana\Core\Services\Trace;

class Manager
{
    /** @var Span[] */
    protected array $stack = [];

    /** @var Span[] */
    protected array $roots = [];

    public function start(string $name): Span
    {
        $parent = $this->current();

        $span = new Span($name, $parent);

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
            $span->end();
        }

        return $span;
    }

    public function record(mixed $data): void
    {
        if ($span = $this->current()) {
            $span->record($data);
        }
    }

    public function current(): ?Span
    {
        return end($this->stack) ?: null;
    }

    /**
     * Vrátí celý strom – použije Worker / Logger / Tracker
     */
    public function spans(): array
    {
        return $this->roots;
    }

    /**
     * Reset mezi requesty / joby / workery
     */
    public function flush(): void
    {
        $this->stack = [];
        $this->roots = [];
    }
}
