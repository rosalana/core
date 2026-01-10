<?php

namespace Rosalana\Core\Logging\Renderers;

use Rosalana\Core\Services\Logging\LogEntry;
use Rosalana\Core\Services\Trace\Trace;

class OutpostMessageRenderer extends Console
{
    public function render(Trace $trace, array $logs): void
    {
        $timestamp = date('Y-m-d H:i:s');

        $mainEntry = $this->findMainEntry($logs);
        $hasErrors = $this->hasErrors($logs);

        if (!$mainEntry) {
            $this->line(sprintf(
                "[%s] %s %s ........ ~ %s",
                $timestamp,
                $this->color('✗', 'red'),
                $this->color('Received message could not be recognized', 'red'),
                $this->styleTime($trace->duration() ?? 0.0)
            ));
            $this->separator();
            return;
        }

        $ok = !$hasErrors;

        $this->line(sprintf(
            "[%s] %s %s (%s) ........ ~ %s",
            $timestamp,
            $ok ? $this->color('✓', 'green') : $this->color('✗', 'red'),
            $this->styleNamespace($mainEntry),
            $mainEntry->getFlag('from')?->value ?? '-',
            $this->styleTime($trace->duration() ?? 0.0)
        ));

        $this->renderHandlers($logs);
        $this->renderSend($logs);

        $this->separator();
    }

    protected function findMainEntry(array $logs): ?LogEntry
    {
        foreach ($logs as $entry) {
            if ($entry->getActor()?->value === 'message') {
                return $entry;
            }
        }
        return null;
    }

    protected function hasErrors(array $logs): bool
    {
        foreach ($logs as $entry) {
            if ($entry->getStatus()?->value === 'error') {
                return true;
            }
        }
        return false;
    }

    protected function renderHandlers(array $logs): void
    {
        $handlerEntries = array_filter($logs, function ($entry) {
            $actor = $entry->getActor()?->value;
            return in_array($actor, ['promise', 'listener', 'registry']);
        });

        if (empty($handlerEntries)) {
            return;
        }

        $this->line('');

        foreach ($handlerEntries as $entry) {
            $actor = $entry->getActor()?->value;
            $message = $entry->getMessage()?->value ?? '';
            $status = $entry->getStatus()?->value ?? 'info';

            $silent = $entry->getFlag('silent')?->value ?? false;
            $queued = $entry->getFlag('queue')?->value ?? false;
            $broadcast = $entry->getFlag('broadcast')?->value ?? false;

            $helpers = $this->styleHelpers((bool) $queued, (bool) $broadcast);
            $silentText = $silent ? $this->color(' (silent)', 'gray') : '';

            if ($status === 'error') {
                $file = $entry->getFlag('file')?->value ?? '';
                $this->line(sprintf(
                    " ↪  [%s%s]: %s",
                    $this->styleVia('failed:' . $actor) . $silentText,
                    '',
                    str_replace(base_path(), '', $file)
                ));
            } else {
                $this->line(sprintf(
                    " ↪  [%s%s%s]: %s",
                    $this->styleVia($actor) . $silentText,
                    $helpers,
                    '',
                    $message
                ));
            }
        }
    }

    protected function renderSend(array $logs): void
    {
        $outpostSend = null;
        $basecampSend = null;

        foreach ($logs as $entry) {
            $actor = $entry->getActor()?->value;
            if ($actor === 'Service:Outpost') {
                $outpostSend = $entry;
            }
            if ($actor === 'Service:Basecamp') {
                $basecampSend = $entry;
            }
        }

        if ($outpostSend) {
            $message = $outpostSend->getMessage()?->value ?? '';
            $targets = $this->formatTargets($outpostSend);

            $parts = explode(':', $message);
            $name = $parts[0] ?? 'unknown';
            $status = strtoupper($parts[1] ?? 'SEND');

            $this->line(sprintf(
                " ↪  [%s%s]: %s",
                $this->styleVia('OUTPOST:' . $status),
                $targets ? ' → ' . $targets : '',
                $name
            ));
        }

        if ($basecampSend) {
            $message = $basecampSend->getMessage()?->value ?? '';
            $status = $basecampSend->getStatus()?->value ?? 'info';

            $parts = explode(' ', $message);
            $method = strtoupper($parts[0] ?? 'REQUEST');
            $endpoint = $parts[1] ?? '-';

            $target = $basecampSend->getFlag('target')?->value ?? '';

            if ($status === 'error') {
                $this->line(sprintf(
                    " ↪  [%s]: %s",
                    $this->styleVia('failed:BASECAMP:' . $method),
                    $this->color($message, 'red')
                ));
            } else {
                $this->line(sprintf(
                    " ↪  [%s%s]: %s",
                    $this->styleVia('BASECAMP:' . $method),
                    $target ? ' → ' . $target : '',
                    $endpoint
                ));
            }
        }
    }

    protected function formatTargets(LogEntry $entry): string
    {
        $targets = [];
        foreach ($entry->getFlags() as $flag) {
            if ($flag->getName() === 'target') {
                $targets[] = $flag->value;
            }
        }
        return implode(', ', $targets);
    }

    protected function styleNamespace(LogEntry $entry): string
    {
        $message = $entry->getMessage()?->value ?? 'unknown';
        $result = $message;

        return $this->color($result, 'cyan');
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
}