<?php

namespace Rosalana\Core\Support\Configure;

use Illuminate\Support\Collection;

class Reader
{
    public function __construct(protected string $file)
    {
        if (!file_exists($this->file)) {
            throw new \RuntimeException("Configuration file not found: {$this->file}");
        }
    }

    public function read(): Collection
    {
        return collect();
    }
}