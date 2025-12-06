<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;
use Rosalana\Core\Contracts\Configure\Node as NodeInterface;
use Rosalana\Core\Support\Configure;

abstract class Node implements NodeInterface
{
    protected ParentNode|Configure|null $parent;

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
        $instance = static::make(start: 0, end: 0, raw: []);
        $instance->setKey($key)->created = true;

        return $instance;
    }

    abstract public static function parse(array $content): Collection;

    abstract public function render(): array;

    public function key(): string
    {
        return $this->key;
    }

    /**
     * Set the key of the node.
     * 
     * @return self
     */
    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }


    /**
     * Get the name of the node.
     * 
     * @return string
     */
    public function name(): string
    {
        $parts = explode('.', $this->key);
        return $parts[array_key_last($parts)];
    }

    /**
     * Rename the node.
     * 
     * @return self
     */
    public function rename(string $name): self
    {
        $parts = explode('.', $this->key());
        $parts[array_key_last($parts)] = $name;

        return $this->setKey(implode('.', $parts));
    }

    public function start(): int
    {
        return $this->start;
    }

    public function end(): int
    {
        return $this->end;
    }

    /**
     * Set new start and end line numbers based on given start line.
     * And keep the distance between start and end.
     * 
     * @return self
     */
    public function moveTo(int $line): self
    {
        $distance = abs($this->start() - $this->end());

        $this->start = $line;
        $this->end = $line + $distance;

        return $this;
    }

    public function raw(): array
    {
        return $this->raw;
    }

    public function path(): string
    {
        if ($this->parent() instanceof NodeInterface) {
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

    public function depth(): array
    {
        $depths = [];
        foreach ($this->raw as $lineNumber => $line) {
            $trimmed = ltrim($line);
            $depths[$lineNumber] = strlen($line) - strlen($trimmed);
        }
        return $depths;
    }

    public function parent(): ParentNode|Configure|null
    {
        return $this->parent;
    }

    /**
     * Set the parent node of this node.
     * 
     * @param NodeInterface|Configure $parent `Congigure` is used only for `File`
     * @return self
     */
    public function setParent(NodeInterface|Configure $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Get the root configure file of the node.
     * 
     * @return File
     */
    public function root(): File
    {
        $current = $this;

        while ($current->parent() instanceof NodeInterface) {
            if ($current->isRoot()) {
                return $current->parent();
            }

            $current = $current->parent();
        }

        throw new \RuntimeException("Node has no root configure.");
    }

    /**
     * Indicates whether the node is at the root level of the 
     * configuration file.
     * 
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->parent instanceof File;
    }

    public function isSubNode(): bool
    {
        $path = explode('.', $this->path());

        return count($path) > 1 || $this->parent instanceof NodeInterface;
    }

    public function isChildOf(NodeInterface $node): bool
    {
        return $this->parent === $node;
    }

    /**
     * Indicates whether the node was newly created and 
     * does not yet exist in the configuration file.
     * 
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->created;
    }

    public function isDirty(): bool
    {
        return false; // not implemented yet
    }

    /**
     * Ty které nejsou zaindexované
     * tedy třeba se přesouvají - by pořád měli držet svoji původní start a end
     * ale měly by mít přesunuté do záporu (aby se to nepletlo)
     */
    public function isIndexed(): bool
    {
        return $this->start() > 0 && $this->end() > 0;
    }

    public function siblings(): Collection
    {
        if ($this->parent() instanceof ParentNode) {
            return $this->parent->nodes()->filter(fn($node) => $node !== $this);
        }

        return collect();
    }

    /**
     * Get siblings that come after this node.
     */
    public function siblingsAfter(): Collection
    {
        return $this->siblings()->filter(fn($node) => $node->start() > $this->end());
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
    public function remove(): NodeInterface
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
            'was_created' => $this->isNew(),
        ];
    }

    public function __call($name, $arguments)
    {
        if ($this->parent() === null) {
            throw new \BadMethodCallException("Method {$name} does not exist on " . static::class);
        }

        return $this->parent()->$name(...$arguments);
    }
}
