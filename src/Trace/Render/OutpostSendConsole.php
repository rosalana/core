<?php

namespace Rosalana\Core\Trace\Render;

use Rosalana\Core\Services\Trace\Trace;
use Rosalana\Core\Trace\Target\Console;

final class OutpostSendConsole extends Console
{
    public function render(Trace $trace): void
    {
        $record = $trace->getDecision();
        $decisiton = $record['data'];

        $this->time($record['timestamp']);
        $this->space();

        $this->token('Outpost', 'cyan');
        $this->space();
        $this->arrow('r');
        $this->space();

        $targets = [];

        foreach ($decisiton['targets'] as $target) {
            if ($target === '*') {
                $targets[] = 'broadcast';
            }

            if (str_starts_with($target, '!')) {
                $targets[] = 'except:' . substr($target, 1);
            } else {
                $targets[] = $target;
            }
        }

        $this->token(implode(',', $targets));
        $this->space();

        $this->token('|', 'gray');
        $this->space();

        $this->token($decisiton['name']);
        $this->token(':');
        $this->outpostMethod($decisiton['status']);

        $this->space();
        $this->dot(5);
        $this->space();
        $this->duration($trace->duration());
    }

    public function renderException(Trace $trace): void
    {
        $record = $trace->getException();
        $exception = $record['exception'];

        $this->time($record['timestamp']);
        $this->space();

        $this->token('failed:', 'red');
        $this->token('Outpost', 'cyan');
        $this->space();
        $this->arrow('r');
        $this->space(2);

        $this->token(get_class($exception), 'red');
        $this->newLine();
        $this->arrow('dr');
        $this->token($exception->getMessage(), 'red');
        $this->newLine();
        $this->arrow('dr');
        $this->token('in', 'gray');
        $this->space();
        $this->token($exception->getFile() . ':' . $exception->getLine(), 'gray');

        $this->newLine();
    }
}
