<?php

namespace Rosalana\Core\Logging\Renderers;

use Rosalana\Core\Services\Logging\LogRenderer;
use Rosalana\Core\Services\Trace\Trace;

class File extends LogRenderer
{
    public function render(Trace $trace, array $logs): void
    {
        foreach ($logs as $entry) {
            $this->line(sprintf(
                '[%s] %s',
                $entry->getActor()?->value,
                $entry->getMessage()?->value,
            ), ['status' => $entry->getStatus()?->value ?? 'info']);
        }
    }

    public function publish(array $rendered): void
    {
        foreach ($rendered as $line) {
            match ($line->meta['status'] ?? 'info') {
                'info' => logger()->info($line->output),
                'warning' => logger()->warning($line->output),
                'error' => logger()->error($line->output),
                'debug' => logger()->debug($line->output),
                default => logger()->info($line->output),
            };
        }
    }
}
