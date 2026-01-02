<?php

namespace Rosalana\Core\Services\Trace;

final class Scope
{
    private bool $closed = false;

    public function __construct(
        private readonly Context $context,
        private readonly Trace $trace,
    ) {}

    public function id(): string
    {
        return $this->trace->id();
    }

    public function close(): void
    {
        if ($this->closed) return;
        $this->closed = true;

        $this->context->end($this->trace);
    }

    public function __destruct()
    {
        $this->close();
    }
}
