<?php

namespace Rosalana\Core\Trace\Render;

use Rosalana\Core\Services\Trace\Trace;
use Rosalana\Core\Trace\Target\Console;

final class BasecampSendConsole extends Console
{
    public function render(Trace $trace): void
    {
        $record = $trace->getDecision();
        $decisiton = $record['data'];

        $this->time($record['timestamp']);
        $this->space();

        $this->token('Basecamp', 'cyan');
        $this->space();
        $this->arrow('r');
        $this->space();
        $this->token($decisiton['target']);

        $this->space();
        $this->token('|', 'gray');
        $this->space();

        $this->httpMethod($decisiton['method']);
        $this->space();

        $this->token($decisiton['endpoint']);
        $this->space();
        $this->token('(');
        $this->httpStatus($decisiton['status']);
        $this->token(')');

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
        $this->token('Basecamp', 'cyan');
        $this->space();
        $this->arrow('r');
        $this->space(2);

        $this->token(get_class($exception), 'red');
        $this->newLine();
        $this->arrow('dr');
        $this->space();
        $this->token($exception->getMessage(), 'red');
        $this->newLine();
        $this->arrow('dr');
        $this->space();
        $this->token('in', 'gray');
        $this->space();
        $this->token($exception->getFile() . ':' . $exception->getLine(), 'gray');

        $this->newLine();
    }
}
