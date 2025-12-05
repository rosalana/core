<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

abstract class ParentNode extends Node
{
    protected Collection $nodes;

    protected int $indent = 1; /** {num}*4 = počet ' ' */

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
        return $this;
    }

    public function removeChild(Node|self $node): self
    {
        return $this;
    }

    public function clearChildren(): self
    {
        return $this;
    }

    public function hasChild(Node|self $node): bool
    {
        return false;
    }

    public function getChild(string $name): Node|self|null
    {
        return null;
    }
}