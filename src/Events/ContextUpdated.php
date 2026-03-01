<?php

namespace Rosalana\Core\Events;

class ContextUpdated
{
    public function __construct(
        public readonly string $scope,
        public readonly string $path,
        public readonly mixed $previous,
        public readonly mixed $current,
    ) {}
}