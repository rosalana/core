<?php

namespace Rosalana\Core\Support\Configure;

class Writer
{
    public function __construct(protected string $file)
    {
        if (!file_exists($this->file)) {
            throw new \RuntimeException("Configuration file not found: {$this->file}");
        }
    }
}
