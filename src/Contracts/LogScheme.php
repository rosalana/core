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
     * @return void
     */
    public function format(Trace $trace): void;

    /**
     * Format exception traces into log entries.
     * 
     * @param Trace $trace
     * @return void
     */
    public function formatException(Trace $trace): void;

    /**
     * Build data from the formatted trace.
     * 
     * @param Trace $trace
     * @return LogEntry[]
     */
    public function build(Trace $trace): iterable;

    /**
     * Build data from the formatted exception trace.
     * 
     * @param Trace $trace
     * @return LogEntry[]
     */
    public function buildException(Trace $trace): iterable;
}
