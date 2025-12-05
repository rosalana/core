<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

abstract class ParentNode extends Node
{
    protected Collection $nodes;

    protected int $indent = 1;
    /** {num}*4 = počet ' ' */

    public function __construct(int $start, int $end, array $raw)
    {
        parent::__construct($start, $end, $raw);
        $this->nodes = collect();
    }

    abstract public static function parse(array $nodes): Collection;

    abstract public function render(): array;

    public function nodes(): Collection
    {
        return $this->nodes;
    }

    public function addChild(Node|self $node): self
    {
        $node->setParent($this);

        // set indexes!!

        $this->nodes->push($node);

        return $this;
    }

    public function removeChild(Node|self $node): self
    {
        $this->nodes = $this->nodes->reject(fn($n) => $n === $node)->values();

        return $this;
    }

    public function clearChildren(): self
    {
        $this->nodes = collect();

        return $this;
    }

    public function hasChild(Node|self $node): bool
    {
        return $this->nodes->contains($node);
    }

    public function getChild(string $name): Node|self|null
    {
        foreach ($this->nodes as $node) {
            if ($node->key() === $name) {
                return $node;
            }
        }

        return null;
    }
}
