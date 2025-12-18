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

    protected function reordered(Node|self $a, Node|self $b): Collection
    {
        $nodes = $this->nodes->values();

        $i = $nodes->search($a);
        $j = $nodes->search($b);

        if ($i === false || $j === false) {
            return collect();
        }

        $nodes[$i] = $b;
        $nodes[$j] = $a;

        return $nodes;
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
    public function value(string $key, ?string $addValue = null): Value
    {
        $node = $this->getChild($key);

        if ($node instanceof Value) {
            return $node;
        }

        $value = Value::makeEmpty($key);
        $this->addChild($value);

        if (! is_null($addValue)) {
            $value->add($addValue);
        }

        return $value;
    }

    /**
     * Get or create a RichComment node by key.
     * 
     * @param string $key
     * @param string|null $description
     * @return RichComment
     */
    public function comment(string $key, ?string $description = null): RichComment
    {
        $comment = RichComment::makeEmpty($key, $description);
        $this->addChild($comment);

        return $comment;
    }

    /**
     * Get the number of spaces for tab.
     * 
     * @return int
     */
    public function indent(): int
    {
        return 4;
    }

    public function swapChildren(Node|self $first, Node|self $second): self
    {
        $cursor = $this->start();

        if ($this instanceof Section) {
            $cursor += 1; // account for opening brace
        }

        $reordered = $this->reordered($first, $second);

        foreach ($reordered as $index => $node) {
            if ($index > 0) {
                $previous = $reordered[$index - 1];
                $cursor += max($previous->padding(), $node->padding());
            }

            $node->moveTo($cursor);
            $cursor += $node->scale() - ($node->padding() * 2);

            if ($index === $reordered->count() - 1) {
                $cursor += $node->padding();
            }
        }

        if ($cursor < $this->end()) {
            $this->scaleDown($this->end() - $cursor);
        }

        if ($cursor > $this->end()) {
            $this->scaleUp($cursor - $this->end());
        }

        $this->nodes = $reordered->values();

        return $this;
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

            $extraPadding = 0;

            if ($this->nodes->isEmpty()) {
                $node->moveTo($this->start + 1 + $node->padding());
            } else {
                $lastChild = $this->nodes->last();

                if ($lastChild->padding() != $node->padding()) {
                    $extraPadding = $lastChild->padding();
                }

                $node->moveTo($lastChild->end() + 1 + $node->padding() + $extraPadding);
            }

            $this->scaleUp($node->scale() + $extraPadding);
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
