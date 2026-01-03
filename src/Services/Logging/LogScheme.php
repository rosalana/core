<?php

namespace Rosalana\Core\Services\Logging;

use Rosalana\Core\Contracts\LogScheme as LogSchemeInterface;
use Rosalana\Core\Services\Trace\Trace;

abstract class LogScheme implements LogSchemeInterface
{
    protected array $entries = [];

    protected function entries(): iterable
    {
        return $this->entries;
    }

    protected function entry(...$arg): LogEntry
    {
        $entry = new LogEntry($arg);

        $this->entries[] = $entry;

        return $entry;
    }

    public function build(Trace $trace): iterable
    {
        $this->entries = []; // reset per call
        $this->format($trace);

        return $this->entries();
    }

    public function buildException(Trace $trace): iterable
    {
        $this->entries = [];
        $this->formatException($trace);

        return $this->entries();
    }

    public function formatException(Trace $trace): void
    {
        $exception = $trace->getException()['exception'];

        $this->entry(
            actor: 'exception',
            flags: [
                'file' => $exception->getFile(),
                'line' => (string) $exception->getLine(),
            ],
            message: $exception->getMessage(),
        );
    }
}
