<?php

namespace Rosalana\Core\Events;

class ContextCleared
{
    public function __construct(
        public readonly string $scope,
        public readonly mixed $previous,
    ) {}
}
