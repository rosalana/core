<?php

namespace Rosalana\Core\Services\Logging;

use Rosalana\Core\Services\Trace\Trace;

abstract class LogRenderer
{
    /**
     * @var array<RenderedLogEntry>
     */
    protected array $lines = [];

    public function __construct(protected Trace $trace) {}

    public function process(): void
    {
        $entries = $this->getEntries($this->trace);

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
        $scheme = null;
        $maxScore = 0;

        foreach (LogRegistry::getSchemes() as $name => $schemeClass) {
            $score = $this->schemeMatchScore($trace->name(), $name);

            if ($score > $maxScore) {
                $maxScore = $score;
                $scheme = $schemeClass;
            }
        }

        if ($scheme) {
            return new $scheme($trace);
        }

        return null;
    }

    /**
     * Calculate the match score between a trace name and a scheme pattern.
     * 
     * @param string $name
     * @param string $pattern may include wildcards (*) and variants ({opt1|opt2})
     * @return int score (0 = no match, higher is better)
     */
    private function schemeMatchScore(string $name, string $pattern): int
    {
        if ($pattern === '*') return 1;

        $variants = $this->generateVariants($pattern);

        $matching = [];
        foreach ($variants as $v) {
            if (fnmatch($v, $name, FNM_NOESCAPE)) {
                $matching[] = $v;
            }
        }

        if (empty($matching)) return 0;

        $variantCount = max(1, count($variants));

        $bestStars = PHP_INT_MAX;
        $bestLiteralLen = 0;

        foreach ($matching as $v) {
            $stars = substr_count($v, '*');
            $literalLen = strlen(str_replace('*', '', $v));

            if ($stars < $bestStars) {
                $bestStars = $stars;
                $bestLiteralLen = $literalLen;
            } elseif ($stars === $bestStars && $literalLen > $bestLiteralLen) {
                $bestLiteralLen = $literalLen;
            }
        }

        $score = intdiv(100000, $variantCount);
        $score = intdiv($score, 1 + ($bestStars * $bestStars * 25));
        $score += $bestLiteralLen;

        return min(100000, max(1, $score));
    }

    /**
     * Generate all pattern variants by expanding {opt1|opt2} constructs.
     * 
     * @param string $pattern
     * @return array<string>
     */
    private function generateVariants(string $pattern): array
    {
        if (!preg_match('/\{([^}]+)\}/', $pattern, $m, PREG_OFFSET_CAPTURE)) {
            return [$pattern];
        }

        $full = $m[0][0];
        $pos  = $m[0][1];
        $len  = strlen($full);
        $opts = explode('|', $m[1][0]);

        $out = [];

        foreach ($opts as $opt) {
            $next = substr_replace($pattern, $opt, $pos, $len);

            foreach (self::generateVariants($next) as $v) {
                $out[] = $v;
            }
        }

        return array_values(array_unique($out));
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
