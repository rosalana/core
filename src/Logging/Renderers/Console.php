<?php

namespace Rosalana\Core\Logging\Renderers;

use Rosalana\Core\Services\Logging\LogRenderer;
use Rosalana\Core\Services\Trace\Trace;

class Console extends LogRenderer
{
    public function render(Trace $trace, array $logs): void
    {
        foreach ($logs as $entry) {
            $this->line(sprintf(
                '[%s] %s: %s',
                date('H:i:s', $entry->getTimestamp()),
                $entry->getActor()?->value,
                $entry->getMessage()?->value,
            ));
        }
    }

    public function publish(array $rendered): void
    {
        foreach ($rendered as $line) {
            echo $line->output . PHP_EOL;
        }
    }
}
