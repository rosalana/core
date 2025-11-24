<?php

namespace Rosalana\Core\Support;

use Illuminate\Support\Collection;
use Rosalana\Core\Support\Configure\Reader;
use Rosalana\Core\Support\Configure\Section;
use Rosalana\Core\Support\Configure\Writer;

class Configure
{
    protected Collection $nodes;

    protected Reader $reader;

    protected Writer $writer;

    public function __construct(protected string $file)
    {
        $this->reader = new Reader($this->file);
        $this->writer = new Writer($this->file);
    }

    public static function file(string $name): self
    {
        if (!str_ends_with($name, '.php')) $name .= '.php';

        return (new self(config_path($name)))->read();
    }

    protected function read(): self
    {
        $this->nodes = $this->reader->read()
            ->each(fn($node) => $node->setParent($this));

        return $this;
    }

    public function section(string $name): self
    {
        return $this;
    }

    public function nodes(): Collection
    {
        return $this->nodes;
    }


    protected function activate(string $name): Section
    {
        return new Section();
    }
}
