<?php

namespace Rosalana\Core\Trace\Render;

use Rosalana\Core\Services\Trace\Trace;
use Rosalana\Core\Trace\Target\Console;

final class OutpostHandlerConsole extends Console
{
    public function render(Trace $trace): void
    {
        $name = $this->getHandlerName($trace);

        if ($name === 'registry') {
            foreach ($trace->phases() as $i => $phase) {
                $silent = $phase->findRecords(fn($r) => $r['data']['silent'] ?? false)[0]['data']['silent'] ?? false;
                $wildcard = $phase->findRecords(fn($r) => $r['data']['wildcard'] ?? false)[0]['data']['wildcard'] ?? false;

                if ($exception = $phase->getRecordByType('exception')) {

                    $this->formatHandlerException(
                        trace: $phase,
                        name: $name,
                        exception: $exception['exception'],
                        silent: $silent,
                        wildcard: $wildcard
                    );
                } elseif ($decision = $phase->getRecordByType('decision')) {
                    $this->formatHandler(
                        trace: $phase,
                        name: $name,
                        handler: $decision['data']['handler'],
                        broadcast: $decision['data']['broadcast'] ?? false,
                        queue: $decision['data']['queued'] ?? false,
                        silent: $silent,
                        wildcard: $wildcard
                    );
                }

                if ($i < count($trace->phases()) - 1) {
                    $this->newLine();
                }
            }
        } else {
            $record = $trace->getDecision();

            $silent = $trace->findRecords(fn($r) => $r['data']['silent'] ?? false)[0]['data']['silent'] ?? false;
            $wildcard = $trace->findRecords(fn($r) => $r['data']['wildcard'] ?? false)[0]['data']['wildcard'] ?? false;

            $this->formatHandler(
                trace: $trace,
                name: $name,
                handler: $record['data']['handler'],
                broadcast: $record['data']['broadcast'] ?? false,
                queue: $record['data']['queued'] ?? false,
                silent: $silent,
                wildcard: $wildcard
            );
        }
    }

    public function renderException(Trace $trace): void
    {
        $name = $this->getHandlerName($trace);
        $exception = $trace->getException()['exception'];

        $silent = $trace->findRecords(fn($r) => $r['data']['silent'] ?? false)[0]['data']['silent'] ?? false;
        $wildcard = $trace->findRecords(fn($r) => $r['data']['wildcard'] ?? false)[0]['data']['wildcard'] ?? false;

        $this->formatHandlerException(
            trace: $trace,
            name: $name,
            exception: $exception,
            silent: $silent,
            wildcard: $wildcard
        );
    }

    private function formatHandler(Trace $trace, string $name, string $handler, bool $broadcast = false, bool $queue = false, bool $silent = false, bool $wildcard = false): void
    {
        $this->time($trace->startTime());
        $this->space();

        $this->token('Outpost', 'cyan');
        $this->token(':');
        $this->token($name);

        $this->space();

        if ($silent) {
            $this->token('(silent)', 'gray');
            $this->space();
        }

        if ($wildcard) {
            $this->token('(wildcard)', 'gray');
            $this->space();
        }

        if ($broadcast) {
            $this->token('[broadcast]', 'red');
            $this->space();
        }

        if ($queue) {
            $this->token('[queued]', 'yellow');
            $this->space();
        }

        $this->token('|', 'gray');
        $this->space();

        $this->token($handler);
    }

    private function formatHandlerException(Trace $trace, string $name, \Throwable $exception, bool $silent = false, bool $wildcard = false): void
    {
        $this->time($trace->startTime());
        $this->space();

        $this->token('failed:', 'red');
        $this->token('Outpost', 'cyan');
        $this->token(':');
        $this->token($name);

        $this->space();

        if ($silent) {
            $this->token('(silent)', 'gray');
            $this->space();
        }

        if ($wildcard) {
            $this->token('(wildcard)', 'gray');
            $this->space();
        }

        $this->token($exception->getMessage(), 'red');

        $this->newLine();
        $this->arrow('dr');
        $this->space();
        $this->token('in', 'gray');
        $this->space();
        $this->token($exception->getFile() . ':' . $exception->getLine(), 'gray');
    }

    private function getHandlerName(Trace $trace): string
    {
        return explode('Outpost:handler:', $trace->name())[1] ?? 'unknown';
    }
}
