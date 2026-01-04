<?php

namespace Rosalana\Core\Services\Logging;

use Rosalana\Core\Contracts\LogScheme as LogSchemeInterface;
use Rosalana\Core\Services\Trace\Trace;

abstract class LogScheme implements LogSchemeInterface
{
    /**
     * @var LogEntry[]
     */
    protected array $entries = [];

    protected int $sequence = 0;

    public function __construct(protected Trace $trace) {}

    /**
     * Get all built log entries.
     * 
     * @return LogEntry[]
     */
    protected function entries(): array
    {
        return $this->entries;
    }

    /**
     * Create and add a log entry.
     * 
     * @param string|null $actor
     * @param array|null $flags
     * @param string|null $message
     * @param LogNode ...$nodes
     * @return LogEntry
     */
    protected function entry(
        ?string $actor = null,
        ?array $flags = null,
        ?string $message = null,
        LogNode ...$nodes
    ): LogEntry {
        $entry = LogEntry::make($actor, $flags, $message, ...$nodes);
        $entry->setTimestamp($this->trace->startTime());
        $entry->setSequence($this->sequence++);

        $this->entries[] = $entry;

        return $entry;
    }


    /**
     * Get the trace instance.
     * 
     * @return Trace
     */
    protected function trace(): Trace
    {
        return $this->trace;
    }

    /**
     * Build log entries.
     * 
     * @return LogEntry[]
     */
    public function build(): array
    {
        $this->entries = [];
        $this->sequence = 0;
        
        $this->format();

        return $this->entries();
    }

    /**
     * Build exception log entries.
     * 
     * @return LogEntry[]
     */
    public function buildException(): array
    {
        $this->entries = [];
        $this->sequence = 0;

        $this->formatException();

        return $this->entries();
    }

    public function formatException(): void
    {
        $record = $this->trace()->getException();
        $exception = $record['exception'];

        $this->entry(
            actor: 'exception',
            flags: [
                'file' => $exception->getFile(),
                'line' => (string) $exception->getLine(),
            ],
            message: $exception->getMessage(),
        )->setTimestamp($record['timestamp']);
    }
}
