<?php

namespace Rosalana\Core\Events;

class ContextFlushed
{
    public function __construct(
        public readonly mixed $previous,
    ) {}
}
