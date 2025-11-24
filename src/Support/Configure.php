<?php

namespace Rosalana\Core\Support;

use Illuminate\Support\Collection;
use Rosalana\Core\Contracts\Configure\Node;
use Rosalana\Core\Support\Configure\Node\RichComment;
use Rosalana\Core\Support\Configure\Node\Section;
use Rosalana\Core\Support\Configure\Node\Value;
use Rosalana\Core\Support\Configure\Reader;
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
        $this->reader->read()
            ->each(fn($node) => $this->addNode($node));

        return $this;
    }

    public function nodes(): Collection
    {
        return $this->nodes;
    }

    public function addNode(Node $node): self
    {
        $this->nodes->push($node->setParent($this));

        return $this;
    }

    public function section(string $path): Node
    {
        return new Section(0, 0, []);
    }

    public function value(string $path): Node
    {
        return new Value(0, 0, [], 's', 's');
    }

    /**
     * Create a rich or simple comment node.
     * If description is provided, a rich comment is created.
     * Otherwise, a simple comment is created.
     */
    public function comment(string $label, ?string $description = null): Node
    {
        if ($description) {
            return new RichComment(0, 0, [], $label, $description);
        }

        return new RichComment(0, 0, [], $label, null); // for now
    }

    public function add(string $path, string $value): Node
    {
        return new Value(0, 0, [], '', '');
    }

    public function set(string $path, string $value): Node
    {
        return new Value(0, 0, [], '', '');
    }

    public function remove(string $path): self
    {
        return $this;
    }

    public function save(): void
    {
        //
    }

    protected function findParent(string $path): Node|self
    {
        return $this;
    }


    public function toArray(): array
    {
        return $this->nodes->map(fn($node) => $node->toArray())->toArray();
    }
}
