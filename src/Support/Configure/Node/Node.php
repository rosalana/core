<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;
use Rosalana\Core\Contracts\Configure\Node as NodeInterface;
use Rosalana\Core\Support\Configure as Root;

abstract class Node implements NodeInterface
{
    protected Node|Root|null $parent;

    protected string $key;
    protected bool $created = false;

    public function __construct(
        protected int $start,
        protected int $end,
        protected array $raw,
    ) {}

    public static function make(...$arg): static
    {
        return new static(...$arg);
    }

    public static function makeEmpty(string $key): static
    {
        return static::make(
            start: 0,
            end: 0,
            raw: []
        )->setKey($key)->setCreated(true);
    }

    abstract public static function parse(array $content): Collection;

    abstract public function render(): array;

    public function setCreated(bool $created = true): self
    {
        $this->created = $created;
        return $this;
    }

    protected function indexRender(Collection $lines): array
    {
        $depth = array_first($this->depth());

        return $lines->mapWithKeys(function ($line, $index) use ($depth) {
            if ($line instanceof Collection) return $line;

            return [$this->startLine() + $index => str_repeat(' ', $depth) . $line];
        })->toArray();
    }

    public function wasCreated(): bool
    {
        return $this->created;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function name(): string
    {
        $parts = explode('.', $this->key);
        return $parts[array_key_last($parts)];
    }

    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    public function rename(string $name): self
    {
        $parts = explode('.', $this->key());
        $parts[array_key_last($parts)] = $name;

        return $this->setKey(implode('.', $parts));
    }

    public function path(): string
    {
        if ($this->hasParent()) {
            $parentPath = $this->parent()->path();

            if ($parentPath) {
                return $parentPath . '.' . $this->key();
            } else {
                return $this->key();
            }
        } else {
            return $this->key();
        }
    }

    public function has(string $node): bool
    {
        return false;
    }

    public function hasPath(string $path): bool
    {
        return $this->path() === $path;
    }

    public function pathToArray(): array
    {
        return explode('.', $this->path());
    }

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
        return count($this->pathToArray()) > 1 || $this->parent instanceof Node;
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
            'key' => $this->key(),
            'path' => $this->path(),
            'name' => $this->name(),
            'is_root' => $this->isRoot(),
            'is_sub_node' => $this->isSubNode(),
            'parent' => $this->parent()?->key() ?? null,
            'was_created' => $this->wasCreated(),
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
