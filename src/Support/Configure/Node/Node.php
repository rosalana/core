<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;
use Rosalana\Core\Contracts\Configure\Node as NodeInterface;
use Rosalana\Core\Support\Configure as Root;

abstract class Node implements NodeInterface
{
    protected Node|Root|null $parent;

    public function __construct(
        protected int $start,
        protected int $end,
        protected array $raw,
    ) {}

    public static function make(...$arg): static
    {
        return new static(...$arg);
    }

    abstract public static function parse(array $content): Collection;

    abstract public function render(): array;

    public function startLine(): int
    {
        return $this->start;
    }

    public function endLine(): int
    {
        return $this->end;
    }

    public function raw(): array
    {
        return $this->raw;
    }

    public function depth(): array
    {
        $depths = [];
        foreach ($this->raw as $lineNumber => $line) {
            $trimmed = ltrim($line);
            $depths[$lineNumber] = strlen($line) - strlen($trimmed);
        }
        return $depths;
    }

    public function parent(): Node|Root|null
    {
        return $this->parent;
    }

    public function root(): Root
    {
        $current = $this;

        while ($current->hasParent()) {
            if ($current->isRoot()) {
                return $current->parent();
            }

            $current = $current->parent();
        }

        throw new \RuntimeException("Node has no root configure.");
    }

    public function isRoot(): bool
    {
        return $this->parent instanceof Root;
    }

    public function isSubNode(): bool
    {
        return $this->parent instanceof Node;
    }

    public function isChildOf(NodeInterface|Root $node): bool
    {
        return $this->parent === $node;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    public function setParent(NodeInterface|Root $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function siblings(): Collection
    {
        if ($this->hasParent()) {
            return $this->parent->nodes()->filter(fn($node) => $node !== $this);
        }

        return collect();
    }

    // TODO
    public function keepStart(): NodeInterface
    {
        return $this;
    }

    // TODO
    public function keepEnd(): self
    {
        return $this;
    }

    // TODO
    public function before(NodeInterface|string $node): self
    {
        return $this;
    }

    // TODO
    public function after(NodeInterface|string $node): self
    {
        return $this;
    }

    // TODO
    public function remove(): NodeInterface|Root
    {
        return $this->parent();
    }

    public function toArray(): array
    {
        return [
            'type' => class_basename($this),
            'start' => $this->start,
            'end' => $this->end,
            'raw' => $this->raw,
            'depth' => $this->depth(),
        ];
    }

    public function __call($name, $arguments)
    {
        if (! $this->hasParent()) {
            throw new \BadMethodCallException("Method {$name} does not exist on " . static::class);
        }

        return $this->parent()->$name(...$arguments);
    }
}
