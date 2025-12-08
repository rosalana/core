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

    public static function parse(array $nodes): Collection
    {
        return collect();
    }

    /**
     * Wrap nodes into a hierarchical structure.
     * 
     * @param Collection $nodes
     * @return Collection
     */
    abstract public static function wrap(Collection $nodes): Collection;

    abstract public function render(): array;

    public function moveTo(int $line): self
    {
        $distance = abs($this->start() - $this->end());
        $offset = $line - $this->start();
        $this->start = $line;
        $this->end = $line + $distance;

        foreach ($this->nodes as $node) {
            $node->moveTo($node->start() + $offset);
        }

        return $this;
    }

    public function scaleUp(int $lines): self
    {
        $siblingsToMove = $this->siblingsAfter();
        $this->end += $lines;

        $siblingsToMove
            ->each(
                fn($sibling) =>
                $sibling->moveTo($sibling->start() + $lines)
            );

        if ($this->isSubNode()) {
            $this->parent()?->scaleUp($lines);
        }

        return $this;
    }

    public function scaleDown(int $lines): self
    {
        $siblingsToMove = $this->siblingsAfter();
        $this->end -= $lines;

        $siblingsToMove->each(
            fn($sibling) =>
            $sibling->moveTo($sibling->start() - $lines)
        );

        if ($this->isSubNode()) {
            $this->parent()?->scaleDown($lines);
        }

        return $this;
    }

    /**
     * Get the child nodes of this parent node.
     * 
     * @return Collection
     */
    public function nodes(): Collection
    {
        return $this->nodes;
    }

    /**
     * Get or create a Section node by key.
     * 
     * @param string $key
     * @return Section
     */
    public function section(string $key): Section
    {
        $node = $this->getChild($key);

        if ($node instanceof Section) {
            return $node;
        }

        $section = Section::makeEmpty($key);
        $this->addChild($section);

        return $section;
    }

    /**
     * Get or create a Value node by key.
     * 
     * @param string $key
     * @return Value
     */
    public function value(string $key): Value
    {
        $node = $this->getChild($key);

        if ($node instanceof Value) {
            return $node;
        }

        $value = Value::makeEmpty($key);
        $this->addChild($value);

        return $value;
    }

    /** @todo implement comments properly */
    public function comment(string $key, string $description): RichComment
    {
        return RichComment::makeEmpty($key)->setDescription($description);
    }

    /**
     * Get the number of spaces for child nodes indentation.
     * 
     * @return int
     */
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

        if (! $ghost) {

            if ($this->nodes->isEmpty()) {
                $node->moveTo($this->start + 1 + $node->padding());
            } else {
                $lastChild = $this->nodes->last();
                $node->moveTo($lastChild->end() + 1 + $node->padding());
            }

            $this->scaleUp($node->scale());
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
        if (! $this->hasChild($node)) return $this;

        if (! $ghost) {
            $distance = abs($node->start() - $node->end()) + $node->padding();

            $this->scaleDown($distance);
        }

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
        if (! $ghost) {
            $distance = abs($this->start() - $this->end()) - 1;

            $this->scaleDown($distance);
        }

        $this->nodes = collect();

        return $this;
    }

    /**
     * Check if the parent node has the given child node.
     * 
     * @param Node|self $node
     * @return bool
     */
    public function hasChild(Node|self $node): bool
    {
        return $this->nodes->contains($node);
    }

    /**
     * Get a child node by its name.
     */
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
