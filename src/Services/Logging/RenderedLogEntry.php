<?php

namespace Rosalana\Core\Services\Logging;

class RenderedLogEntry
{
    public function __construct(public string $output, public array $meta) {}
}
