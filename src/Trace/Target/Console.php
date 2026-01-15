<?php

namespace Rosalana\Core\Trace\Target;

use Rosalana\Core\Services\Trace\Rendering\Target;
use Rosalana\Core\Services\Trace\Trace;

abstract class Console extends Target
{
    abstract public function render(Trace $trace): void;

    public function publish(array $lines): void
    {
        throw new \Exception('Not implemented');
    }

    public function renderException(Trace $trace): void
    {
        throw new \Exception('Not implemented');
    }
}