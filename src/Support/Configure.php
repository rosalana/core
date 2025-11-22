<?php

namespace Rosalana\Core\Support;

use Illuminate\Support\Collection;
use Rosalana\Core\Support\Configure\Reader;
use Rosalana\Core\Support\Configure\Section;
use Rosalana\Core\Support\Configure\Writer;

class Configure
{
    protected Collection $sections;

    protected ?Section $active = null;

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
        $this->sections = $this->reader->read();

        return $this;
    }

    public function section(string $name): self
    {
        return $this;
    }

    protected function activate(string $name): Section
    {
        return new Section();
    }

}