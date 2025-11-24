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

        $this->nodes = collect();
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
        $parent = $this->resolve($path);
        $key = $this->pathToKey($path);

        if ($parent->hasChild($key)) {
            return $parent->findNode($key);
        } else {
            $section = Section::makeEmpty($key);
            $parent->addNode($section);

            return $section;
        }
    }

    public function value(string $path): Node
    {
        $parent = $this->resolve($path);
        $key = $this->pathToKey($path);

        if ($parent->hasChild($key)) {
            return $parent->findNode($key);
        } else {
            $value = Value::makeEmpty($key);
            $parent->addNode($value);

            return $value;
        }
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

    public function resolve(string $path): Node|self
    {
        $parts = explode('.', $path);
        array_pop($parts);

        $current = $this;

        foreach ($parts as $part) {
            $child = $current->findNode($part);

            if (! $child) {
                $child = Section::makeEmpty($part);
                $current->addNode($child);
            }

            $current = $child;
        }

        return $current;
    }

    public function findNode(string $key): ?Node
    {
        foreach ($this->nodes as $node) {
            if ($node instanceof RichComment) {
                continue;
            }

            if ($node->key() === $key) {
                return $node;
            }
        }

        return null;
    }

    public function hasChild(string $key): bool
    {
        return !! ($this->findNode($key));
    }

    protected function pathToKey(string $path): string
    {
        $parts = explode('.', $path);
        return $parts[array_key_last($parts)];
    }

    public function toArray(): array
    {
        return $this->nodes->map(fn($node) => $node->toArray())->toArray();
    }
}
