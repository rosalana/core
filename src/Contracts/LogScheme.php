<?php

namespace Rosalana\Core\Contracts;

use Rosalana\Core\Services\Logging\LogEntry;
use Rosalana\Core\Services\Trace\Trace;

interface LogScheme
{
    /**
     * Determine if the log scheme matches the given trace.
     * 
     * @param Trace $trace
     * @return bool
     */
    public function match(Trace $trace): bool;

    /**
     * Format the given trace into log entries.
     * 
     * @param Trace $trace
     * @return LogEntry[]
     */
    public function format(Trace $trace): iterable;

    /**
     * Format exception traces into log entries.
     * 
     * @param Trace $trace
     * @return LogEntry[]
     */
    public function exceptionFormat(Trace $trace): iterable;
}
