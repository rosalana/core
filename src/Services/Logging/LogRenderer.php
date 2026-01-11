<?php

namespace Rosalana\Core\Services\Logging;

use Rosalana\Core\Facades\Trace as FacadesTrace;
use Rosalana\Core\Services\Trace\Trace;
use Rosalana\Core\Support\WildcardString;

abstract class LogRenderer
{
    /**
     * @var array<RenderedLogEntry>
     */
    protected array $lines = [];

    public function __construct(protected Trace $trace) {}

    public function process(): void
    {
        if (!FacadesTrace::isEnabled()) {
            $entries = [LogEntry::make(action: 'Logger', message: 'Tracing is disabled.', timestamp: time(), status: 'error')];
        } else {
            $entries = $this->getEntries($this->trace);
        }

        usort($entries, function (LogEntry $a, LogEntry $b) {
            $t = $a->getTimestamp() <=> $b->getTimestamp();
            return $t !== 0 ? $t : ($a->getSequence() <=> $b->getSequence());
        });

        $this->build($this->trace, $entries);
    }

    /**
     * Get rendered lines.
     * 
     * @return array<RenderedLogEntry>
     */
    public function lines(): array
    {
        return $this->lines;
    }

    /**
     * Add a rendered line.
     * 
     * @param string $line
     * @param array<string, mixed> $meta
     * @return void
     */
    public function line(string $line, array $meta = ['status' => 'info']): void
    {
        $this->lines[] = new RenderedLogEntry($line, $meta);
    }

    /**
     * Build the rendered log output.
     * 
     * @param Trace $trace
     * @param LogEntry[] $logs
     * @return void
     */
    private function build(Trace $trace, array $logs): void
    {
        $this->lines = [];
        $this->render($trace, $logs);

        $this->publish($this->lines());
    }

    /**
     * Recursively get log entries from the trace and its phases.
     * 
     * @param Trace $trace
     * @return LogEntry[]
     */
    private function getEntries(Trace $trace): array
    {
        $entries = [];

        $scheme = $this->matchScheme($trace);

        if ($scheme) {
            $entries = $this->buildScheme($scheme, $trace);
        }

        if ($trace->hasPhases()) {
            foreach ($trace->phases() as $phase) {
                $phaseEntries = $this->getEntries($phase);
                $entries = array_merge($entries, $phaseEntries);
            }
        }

        return $entries;
    }

    /**
     * Build log entries using the given scheme.
     * 
     * @param LogScheme $scheme
     * @param Trace $trace
     * @return LogEntry[]
     */
    private function buildScheme(LogScheme $scheme, Trace $trace): array
    {
        try {
            if ($trace->hasRecordType('exception')) {
                return $scheme->buildException();
            } else {
                return $scheme->build();
            }
        } catch (\Throwable $e) {
            // log...
            return [];
        }
    }

    /**
     * Match the best log scheme for the given trace.
     * 
     * @param Trace $trace
     * @return LogScheme|null
     */
    private function matchScheme(Trace $trace): ?LogScheme
    {
        $best = null;
        $bestScore = 0;

        foreach (LogRegistry::getSchemes() as $pattern => $schemeClass) {
            $score = wildcard($pattern)->score($trace->name());

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $schemeClass;
            }
        }

        return $best ? new $best($trace) : null;
    }

    /**
     * Render log entries to the desired format.
     * 
     * @param Trace $trace
     * @param LogEntry[] $logs
     * @return array
     */
    abstract public function render(Trace $trace, array $logs): void;

    /**
     * Publish the rendered logs to the desired destination.
     * 
     * @param array<RenderedLogEntry> $rendered
     * @return void
     */
    abstract public function publish(array $rendered): void;
}
