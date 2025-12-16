<?php

namespace Rosalana\Core\Support\Configure;

use Rosalana\Core\Support\Configure\Node\File;

class Writer
{
    public function __construct(protected File $file)
    {
        if (!$this->file->exists()) {
            throw new \RuntimeException("Configuration file not found: {$this->file->fullName()}");
        }
    }

    public function write(): void
    {
        $render = $this->file->render();

        $this->file->insert($this->render($render));
    }

    protected function render(array $render): array
    {
        // Remove empty lines from the beginning of render
        while (!empty($render) && trim($render[0]) === '') {
            array_shift($render);
        }

        return array_merge(
            $this->file->surroundingLines()['prefix'],
            $render,
            $this->file->surroundingLines()['suffix'],
        );
    }
}
