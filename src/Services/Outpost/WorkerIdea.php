<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Support\Facades\Redis;
use Rosalana\Core\Actions\Outpost\MessageReceived;
use Rosalana\Core\Exceptions\Service\Outpost\OutpostException;
use Rosalana\Core\Facades\App;
use Rosalana\Core\Facades\Trace as TraceFacade;
use Rosalana\Core\Services\Outpost\Message;
use Rosalana\Core\Services\Trace\Trace;

class WorkerIdea
{
    protected string $connection;
    protected string $stream;
    protected string $origin;

    public function __construct(string $origin)
    {
        $this->connection = App::config('outpost.connection', 'default');
        $this->stream = 'outpost:' . App::slug();
        $this->origin = $origin;
    }

    public function __invoke(): void
    {
        $this->ensureConsumerGroup();

        while (true) {
            $messages = Redis::connection($this->connection)->xreadgroup(
                App::slug(),
                gethostname() . '-' . getmypid(),
                [$this->stream => '>'],
                1,
                5000
            );

            if (empty($messages)) {
                continue;
            }

            foreach ($messages[$this->stream] ?? [] as $id => $payload) {
                $trace = null;

                try {
                    TraceFacade::start('Outpost:receive');

                    try {
                        run(new MessageReceived($id, $payload));
                    } catch (OutpostException $e) {
                        // prefer app's own handling; tracing is still useful
                        // (MessageReceived usually marks message failed/unreachable)
                    } catch (\Throwable $e) {
                        // swallow here - we never want worker crash on a single message
                    } finally {
                        $trace = TraceFacade::finish();
                    }
                } catch (\Throwable $e) {
                    // even tracing system should never kill the worker
                    $trace = null;
                } finally {
                    // You already know this needs better lifecycle later – for now keep it safe.
                    try {
                        TraceFacade::flush();
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                // Logging must be safe
                try {
                    if ($trace instanceof Trace) {
                        $this->logTrace($trace);
                    } else {
                        $this->logFallback('Trace is disabled or unavailable.');
                    }
                } catch (\Throwable $e) {
                    $this->logFallback('Trace logging failed: ' . $e->getMessage());
                }

                // ACK must always happen (never block stream)
                try {
                    Redis::connection($this->connection)->xack(
                        $this->stream,
                        App::slug(),
                        [$id]
                    );
                } catch (\Throwable $e) {
                    // if ACK fails, it's infrastructure issue; do not crash loop
                    $this->logFallback('Failed to ACK message: ' . $e->getMessage());
                }
            }
        }
    }

    protected function ensureConsumerGroup(): void
    {
        try {
            Redis::connection($this->connection)->xgroup(
                'CREATE',
                $this->stream,
                App::slug(),
                '0',
                true
            );
        } catch (\Throwable $e) {
            // group probably exists
        }
    }

    /**
     * Pretty + safe trace log.
     */
    protected function logTrace(Trace $trace): void
    {
        $timestamp = date('Y-m-d H:i:s');

        $message = $this->extractMessage($trace);
        $exceptions = $trace->onlyExceptionRecords();

        // Header fallback if message is not detected
        if (! $message instanceof Message) {
            echo sprintf(
                "[%s] %s %s ........ ~ %s\n",
                $timestamp,
                $this->color('✗', 'red'),
                $this->color('Received message could not be recognized', 'red'),
                $this->styleTime($trace->duration() ?? 0.0)
            );

            foreach ($exceptions as $ex) {
                $this->logExceptionRecord($ex);
            }

            $this->line();
            return;
        }

        // Determine overall "ok" state:
        // - if any exception record exists anywhere, show as failed
        $ok = empty($exceptions);

        echo sprintf(
            "[%s] %s %s (%s) ........ ~ %s\n",
            $timestamp,
            $ok ? $this->color('✓', 'green') : $this->color('✗', 'red'),
            $this->styleNamespace($message),
            $message->from ?? '-',
            $this->styleTime($trace->duration() ?? 0.0)
        );

        // Details
        $handle = $this->firstPhaseByName($trace, 'Outpost:handle');
        if ($handle) {
            $this->logHandle($handle);
        }

        $send = $this->firstPhaseByName($trace, 'Outpost:send');
        if ($send) {
            $this->logSend($send);
        }

        // If something failed but wasn't shown by phases, print exceptions at the end
        if (! empty($exceptions)) {
            foreach ($exceptions as $ex) {
                $this->logExceptionRecord($ex);
            }
        }

        $this->line();
    }

    /**
     * Log the "Outpost:handle" section.
     */
    protected function logHandle(Trace $handle): void
    {
        $promise = $this->firstPhaseByName($handle, 'Outpost:promise');
        $registry = $this->firstPhaseByName($handle, 'Outpost:registry');
        $listener = $this->firstPhaseByName($handle, 'Outpost:listener');

        // We only print what matters:
        // - decision (used path)
        // - exception (failed path)
        // - registry can have multiple internal attempts

        echo "\n";

        // Promise
        $this->logProviderPhase('promise', $promise);

        // Registry (special: multiple internal phases)
        if ($registry) {
            $this->logRegistryPhase($registry);
        }

        // Listener
        $this->logProviderPhase('listener', $listener);
    }

    protected function logProviderPhase(string $provider, ?Trace $phase): void
    {
        if (! $phase) return;

        // decision wins
        $decision = $phase->getDecision();
        if ($decision) {
            $data = $this->normalizeDecisionData($decision['data'] ?? null);

            $this->logPhaseDetail(
                $this->styleVia($provider),
                $this->styleHelpers(
                    (bool) ($data['queued'] ?? false),
                    (bool) ($data['broadcasted'] ?? false)
                ),
                $this->formatHandler($data) . '.php'
            );

            return;
        }

        // exception
        $exception = $phase->getException();
        if ($exception && isset($exception['exception']) && $exception['exception'] instanceof \Throwable) {
            $this->logPhaseDetail(
                $this->styleVia('failed:' . $provider),
                '',
                class_basename(base_path(), '', $exception['exception']->getFile())
                // str_replace(base_path(), '', $exception['exception']->getFile())
            );

            return;
        }

        // no decision / no exception -> provider didn't match, don't spam logs
    }

    /**
     * Registry can contain multiple internal attempts.
     * We print only attempts that decided or failed.
     */
    protected function logRegistryPhase(Trace $registry): void
    {
        $subphases = $registry->phases();
        if (empty($subphases)) {
            // registry provider exists, but no internal phases – treat similarly to normal provider
            $this->logProviderPhase('registry', $registry);
            return;
        }

        foreach ($subphases as $sub) {
            // decision
            $decision = $sub->getDecision();
            if ($decision) {
                $data = $this->normalizeDecisionData($decision['data'] ?? null);
                $silent = (bool) ($data['silent'] ?? false);

                $this->logPhaseDetail(
                    $this->styleVia('registry') . ($silent ? $this->color(' (silent)', 'gray') : ''),
                    $this->styleHelpers(
                        (bool) ($data['queued'] ?? false),
                        (bool) ($data['broadcasted'] ?? false)
                    ),
                    $this->formatHandler($data) . '.php'
                );

                continue;
            }

            // exception
            $exception = $sub->getException();
            if ($exception && isset($exception['exception']) && $exception['exception'] instanceof \Throwable) {
                // sometimes silent info can be stored in first record data
                $firstData = $this->firstRecordData($sub);
                $silent = is_array($firstData) ? (bool) ($firstData['silent'] ?? false) : false;

                $this->logPhaseDetail(
                    $this->styleVia('failed:registry') . ($silent ? $this->color(' (silent)', 'gray') : ''),
                    '',
                    str_replace(base_path(), '', $exception['exception']->getFile())
                );

                continue;
            }

            // ignore no-op / non-decision silent attempts
        }
    }

    protected function logSend(Trace $send): void
    {
        // Send decision
        $decision = $send->getDecision();
        if ($decision) {
            $data = $this->normalizeDecisionData($decision['data'] ?? null);

            $status = strtoupper((string) ($data['status'] ?? 'send'));
            $targets = $this->formatTargets($data['targets'] ?? null);
            $name = (string) ($data['name'] ?? 'unknown');

            $this->logPhaseDetail(
                $this->styleVia('OUTPOST:' . $status),
                $targets ? ' → ' . $targets : '',
                $name
            );
        }

        // Basecamp phase (first child phase, if any)
        $basecamp = $send->phases()[0] ?? null;
        if ($basecamp instanceof Trace) {
            $baseDecision = $basecamp->getDecision();
            $baseException = $basecamp->getException();

            if ($baseDecision) {
                $data = $this->normalizeDecisionData($baseDecision['data'] ?? null);

                $method = strtoupper((string) ($data['method'] ?? 'request'));
                $target = (string) ($data['target'] ?? '-');
                $endpoint = (string) ($data['endpoint'] ?? '-');

                $this->logPhaseDetail(
                    $this->styleVia('BASECAMP:' . $method),
                    $target ? ' → ' . $target : '',
                    $endpoint
                );
            }

            if ($baseException && isset($baseException['exception']) && $baseException['exception'] instanceof \Throwable) {
                $method = 'REQUEST';
                if ($baseDecision) {
                    $data = $this->normalizeDecisionData($baseDecision['data'] ?? null);
                    $method = strtoupper((string) ($data['method'] ?? 'request'));
                }

                $this->logPhaseDetail(
                    $this->styleVia('failed:BASECAMP:' . $method),
                    '',
                    $this->color($baseException['exception']->getMessage(), 'red')
                );
            }
        }
    }

    /**
     * Extract Message instance from any record in the trace tree.
     */
    protected function extractMessage(Trace $trace): ?Message
    {
        $records = $trace->findRecords(function ($record) {
            return isset($record['data']) && $record['data'] instanceof Message;
        });

        if (! empty($records)) {
            return $records[0]['data'];
        }

        // fallback: allow nested arrays
        $records = $trace->findRecords(function ($record) {
            $data = $record['data'] ?? null;
            return is_array($data) && isset($data['message']) && $data['message'] instanceof Message;
        });

        if (! empty($records)) {
            return $records[0]['data']['message'];
        }

        return null;
    }

    protected function firstPhaseByName(Trace $trace, string $name): ?Trace
    {
        $phases = $trace->findPhases(fn(Trace $p) => $p->name() === $name);
        return $phases[0] ?? null;
    }

    protected function firstRecordData(Trace $trace): mixed
    {
        $recs = $trace->onlyDataRecords();
        return $recs[0]['data'] ?? null;
    }

    protected function normalizeDecisionData(mixed $data): array
    {
        if (is_array($data)) return $data;

        // allow object-ish decision payloads (DTOs)
        if (is_object($data)) {
            try {
                return (array) $data;
            } catch (\Throwable) {
                return [];
            }
        }

        return [];
    }

    protected function formatHandler(array $decision): string
    {
        // Most common keys (you used various ones)
        foreach (['handler', 'action', 'class', 'listener'] as $k) {
            if (! empty($decision[$k]) && is_string($decision[$k])) {
                return $decision[$k];
            }
        }

        // If decision contains a Message instance, show its name
        if (isset($decision['message']) && $decision['message'] instanceof Message) {
            return $decision['message']->name();
        }

        return $this->color('unknown handler', 'gray');
    }

    protected function formatTargets(mixed $targets): string
    {
        if (is_string($targets)) return $targets;

        if (is_array($targets)) {
            $targets = array_values(array_filter($targets, fn($v) => is_string($v) && $v !== ''));
            return implode(', ', $targets);
        }

        return '';
    }

    protected function logExceptionRecord(array $record): void
    {
        $e = $record['exception'] ?? null;
        if (! $e instanceof \Throwable) return;

        $this->logDetail(sprintf(
            "%s: %s",
            $this->color(class_basename($e::class), 'red'),
            $this->color($e->getMessage(), 'gray')
        ));
    }

    protected function logPhaseDetail(string $via, string $flags, string $text): void
    {
        $this->logDetail(sprintf("[%s%s]: %s", $via, $flags, $text));
    }

    protected function logDetail(string $text): void
    {
        echo sprintf(" ↪  %s\n", $text);
    }

    protected function line(): void
    {
        echo $this->color(str_repeat('-', 80) . "\n\n", 'gray');
    }

    protected function logFallback(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');

        echo sprintf(
            "[%s] %s %s\n\n",
            $timestamp,
            $this->color('!', 'yellow'),
            $this->color($message, 'gray')
        );
    }

    protected function styleNamespace(Message $message): string
    {
        $result = $message->name();

        return match ($message->status()) {
            'request'      => $result . $this->color(':request', 'blue'),
            'confirmed'    => $result . $this->color(':confirmed', 'green'),
            'failed'       => $result . $this->color(':failed', 'red'),
            'unreachable'  => $result . $this->color(':unreachable', 'yellow'),
            default        => $result . ($message->status() ? ":{$message->status()}" : ':unknown'),
        };
    }

    protected function styleVia(string $provider): string
    {
        if (str_starts_with($provider, 'failed:')) {
            return $this->color('failed:', 'red') . $this->styleVia(substr($provider, 7));
        }

        return match ($provider) {
            'promise'  => $this->color('promise', 'cyan'),
            'listener' => $this->color('listener', 'green'),
            'registry' => $this->color('registry', 'blue'),
            default    => $this->color($provider, 'gray'),
        };
    }

    protected function styleHelpers(bool $queued, bool $broadcasted): string
    {
        $flags = [];

        if ($queued) {
            $flags[] = $this->color('queue', 'yellow');
        }

        if ($broadcasted) {
            $flags[] = $this->color('broadcast', 'red');
        }

        return $flags ? ' → ' . implode(',', $flags) : '';
    }

    protected function styleTime(float $time): string
    {
        return match (true) {
            $time >= 50 => $this->color(number_format($time, 2) . 'ms', 'red'),
            $time >= 10 => $this->color(number_format($time, 2) . 'ms', 'yellow'),
            default     => number_format($time, 2) . 'ms',
        };
    }

    protected function color(string $text, string $color): string
    {
        $c = fn(string $code) => "\033[{$code}m";
        $reset = $c('0');

        $gray   = $c('90');
        $green  = $c('32');
        $red    = $c('31');
        $blue   = $c('34');
        $cyan   = $c('36');
        $yellow = $c('33');

        return match ($color) {
            'gray'   => "{$gray}{$text}{$reset}",
            'green'  => "{$green}{$text}{$reset}",
            'red'    => "{$red}{$text}{$reset}",
            'blue'   => "{$blue}{$text}{$reset}",
            'cyan'   => "{$cyan}{$text}{$reset}",
            'yellow' => "{$yellow}{$text}{$reset}",
            default  => $text,
        };
    }
}
