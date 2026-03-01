<?php

namespace Rosalana\Core\Events;

use Rosalana\Core\Services\Outpost\Message;

class OutpostMessageReceived
{
    public function __construct(
        public readonly Message $message,
    ) {}
}
