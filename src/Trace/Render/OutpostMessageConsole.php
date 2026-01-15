<?php

namespace Rosalana\Core\Trace\Render;

use Rosalana\Core\Services\Trace\Trace;
use Rosalana\Core\Trace\Target\Console;

final class OutpostMessageConsole extends Console
{
    public function render(Trace $trace): void
    {
        $record = $trace->getDecision();
        /** @var \Rosalana\Core\Services\Outpost\Message $message */
        $message = $record['data']['message'];

        $this->time($record['timestamp']);
        $this->space();

        $this->token('Outpost', 'cyan');
        $this->space();

        $this->arrow('l');
        $this->space();

        $this->token($message->from);
        $this->space();

        $this->token('|', 'gray');
        $this->space();

        $this->token($message->name());
        $this->token(':');
        $this->outpostMethod($message->status());

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
        $this->arrow('l');
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
