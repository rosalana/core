<?php

namespace Rosalana\Core\Contracts;

use Rosalana\Core\Services\Logging\LogEntry;

interface LogScheme
{
    /**
     * Format the given trace into log entries.
     * 
     * @return void
     */
    public function format(): void;

    /**
     * Format exception traces into log entries.
     * 
     * @return void
     */
    public function formatException(): void;

    /**
     * Build data from the formatted trace.
     * 
     * @return LogEntry[]
     */
    public function build(): array;

    /**
     * Build data from the formatted exception trace.
     * 
     * @return LogEntry[]
     */
    public function buildException(): array;
}
