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
        $this->renderService($logs);

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
                $this->line(sprintf(
                    " ↪  [%s%s]: %s",
                    $this->styleVia('failed:' . $actor) . $silentText,
                    '',
                    $this->color(str_replace(base_path(), '', $message), 'red')
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

    protected function renderService(array $logs): void
    {
        $serviceEntries = array_filter($logs, function ($entry) {
            $actor = $entry->getActor()?->value;
            return str_starts_with($actor ?? '', 'Service:');
        });

        foreach ($serviceEntries as $entry) {
            $actor = $entry->getActor()?->value;
            $message = $entry->getMessage()?->value ?? '';
            $status = $entry->getStatus()?->value ?? 'info';

            if ($actor === 'Service:Outpost') {
                $targets = $this->formatTargets($entry);

                $this->line(sprintf(
                    " ↪  [%s%s]: %s",
                    $this->styleVia($actor),
                    $targets ? ' → ' . $targets : '',
                    $this->styleNamespace($message)
                ));
            } elseif ($actor === 'Service:Basecamp') {
                $target = $entry->getFlag('target')?->value ?? '';
                $parts = explode(' ', $message);
                $method = $parts[0] ?? '';
                $endpoint = $parts[1] ?? $message;
                $httpStatus = $parts[2] ?? null;

                if ($status === 'error') {
                    $this->line(sprintf(
                        " ↪  [%s]: %s",
                        $this->styleVia('failed:' . $actor),
                        $this->color($message, 'red')
                    ));
                } else {
                    $formattedMessage = $method . ' ' . $endpoint;
                    if ($httpStatus) {
                        $formattedMessage .= ' ' . $this->formatHttpStatus($httpStatus);
                    }

                    $this->line(sprintf(
                        " ↪  [%s%s]: %s",
                        $this->styleVia($actor),
                        $target ? ' → ' . $target : '',
                        $formattedMessage
                    ));
                }
            } else {
                $this->line(sprintf(
                    " ↪  [%s]: %s",
                    $this->styleVia($actor),
                    $message
                ));
            }
        }
    }

    protected function formatHttpStatus(string $status): string
    {
        $code = (int) ltrim($status, ':');

        return match (true) {
            $code >= 200 && $code < 300 => $this->color($code, 'green'),
            $code >= 300 && $code < 400 => $this->color($code, 'blue'),
            $code >= 400 && $code < 500 => $this->color($code, 'yellow'),
            $code >= 500 => $this->color($code, 'red'),
            default => $code,
        };
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

    protected function styleNamespace(LogEntry|string $entry): string
    {
        $message = $entry instanceof LogEntry
            ? ($entry->getMessage()?->value ?? 'unknown')
            : $entry;

        if (str_contains($message, ':')) {
            $parts = explode(':', $message, 2);
            $namespace = $parts[0];
            $status = $parts[1];

            $coloredStatus = match (strtolower($status)) {
                'request'      => $this->color($status, 'blue'),
                'confirmed'    => $this->color($status, 'green'),
                'failed'       => $this->color($status, 'red'),
                'unreachable'  => $this->color($status, 'yellow'),
                default        => $status,
            };

            return $namespace . ':' . $coloredStatus;
        }

        return $message;
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