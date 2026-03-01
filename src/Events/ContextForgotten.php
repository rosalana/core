<?php

namespace Rosalana\Core\Events;

class ContextForgotten
{
    public function __construct(
        public readonly string $scope,
        public readonly string $path,
        public readonly mixed $previous,
    ) {}
}
