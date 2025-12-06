<?php

namespace Rosalana\Core\Contracts\Configure;

use Illuminate\Support\Collection;
use Rosalana\Core\Support\Configure;
use Rosalana\Core\Support\Configure\Node\Root;

interface Node
{
    /**
     * Create a new instance of the node.
     * @param mixed ...$arg
     * 
     * @internal
     * @return static 
     */
    public static function make(...$arg): static;

    /**
     * Create an empty instance of the node with the given key.
     * 
     * @param string $key
     * @internal
     * @return static
     */
    public static function makeEmpty(string $key): static;

    /**
     * Go through the content and parse itself.
     * @param array $content
     * @internal
     * @return Collection
     */
    public static function parse(array $content): Collection;

    /**
     * Render the node back to array of lines.
     * 
     * @internal
     * @return array
     */
    public function render(): array;

    /**
     * Get the key of the node.
     * 
     * @internal
     * @return string
     */
    public function key(): string;

    /**
     * Get the line number where the node starts.
     * 
     * @internal
     * @return int
     */
    public function start(): int;

    /**
     * Get the line number where the node ends.
     * 
     * @internal
     * @return int
     */
    public function end(): int;

    /**
     * Get the raw content of the node.
     * It is the cut of the original file lines.
     * 
     * @internal
     * @return array
     */
    public function raw(): array;

    /**
     * Get the full path of the node.
     * 
     * @internal
     * @return string
     */
    public function path(): string;

    /**
     * Get the number of spaces depth of the node in the config file.
     * 
     * @deprecated use parent()->indent()
     * @return array
     */
    public function depth(): array;

    /**
     * Get the parent node or root configure.
     * 
     * @return Node|null
     */
    public function parent(): Node|Configure|null;

    /**
     * Check if the node is a sub-node of another node.
     * 
     * @return bool
     */
    public function isSubNode(): bool;

    /**
     * Check if the node is a direct child of the given node or root.
     * 
     * @param Node|Root $node
     * @return bool
     */
    public function isChildOf(Node $node): bool;

    /**
     * Get the sibling nodes of the current node.
     * 
     * @return Collection
     */
    public function siblings(): Collection;

    /**
     * Move node to the beginning in the parent nodes list.
     * 
     * @return self
     */
    public function keepStart(): self;

    /**
     * Move node to the end in the parent nodes list.
     * 
     * @return self
     */
    public function keepEnd(): self;

    /**
     * Move node before another node in the parent nodes list.
     * 
     * @param Node|string $node
     * @return self
     */
    public function before(Node|string $node): self;

    /**
     * Move node after another node in the parent nodes list.
     * 
     * @param Node|string $node
     * @return self
     */
    public function after(Node|string $node): self;

    /**
     * Remove the node from the parent nodes list.
     * 
     * @return Node
     */
    public function remove(): Node;

    /**
     * Convert the node to array representation.
     * 
     * @return array
     */
    public function toArray(): array;
}
