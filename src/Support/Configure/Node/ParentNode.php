<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

abstract class ParentNode extends Node
{
    protected Collection $nodes;

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

    public function indent(): int
    {
        $depth = $this->path() ? count(explode('.', $this->path())) : 0;

        return ($depth + 1) * 4;
    }

    /**
     * Add a child node to this parent node and reindex.
     * 
     * @param Node|self $node
     * @param bool $ghost If true, the node is added without affecting indexes.
     * @return $this
     */
    public function addChild(Node|self $node, bool $ghost = false): self
    {
        $node->setParent($this);

        if (!$ghost) {
            // set indexes!!
        }

        $this->nodes->push($node);

        return $this;
    }

    /**
     * Remove a child node from this parent node and reindex.
     * 
     * @param Node|self $node
     * @param bool $ghost If true, the node is removed without affecting indexes.
     * @return $this
     */
    public function removeChild(Node|self $node, bool $ghost = false): self
    {
        $this->nodes = $this->nodes->reject(fn($n) => $n === $node)->values();

        return $this;
    }

    /**
     * Clear all child nodes from this parent node and reindex.
     * 
     * @param bool $ghost If true, the children are cleared without affecting indexes.
     * @return $this
     */
    public function clearChildren(bool $ghost = false): self
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

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'nodes' => $this->nodes->map(fn($node) => $node->toArray())->toArray(),
            'indent' => $this->indent(),
        ]);
    }
}
