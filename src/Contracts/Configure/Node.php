<?php

namespace Rosalana\Core\Contracts\Configure;

use Illuminate\Support\Collection;
use Rosalana\Core\Support\Configure as Root;

/**
 * Fix: Každý uzel musí mít $key a $path vlastnosti.
 * 
 * Rozdělit to tak, že $path je ta full $path
 * a $key je ten poslední segment.
 */
interface Node
{
    /**
     * Go through the content and parse itself.
     * @param array $content
     * @return Collection
     */
    public static function parse(array $content): Collection;

    /**
     * Render the node back to array of lines.
     * 
     * @return array
     */
    public function render(): array;

    /**
     * Get the line number where the node starts.
     * 
     * @return int
     */
    public function startLine(): int;

    /**
     * Get the line number where the node ends.
     * 
     * @return int
     */
    public function endLine(): int;

    /**
     * Get the raw content of the node.
     * It is the cut of the original file lines.
     * 
     * @return array
     */
    public function raw(): array;

    /**
     * Get the number of spaces depth of the node in the config file.
     * 
     * @return array
     */
    public function depth(): array;

    // /**
    //  * Get the full path of the node.
    //  */
    // public function path(): string;

    // /**
    //  * Check if the node has the given path.
    //  */
    // public function hasPath(string $path): bool;

    // /**
    //  * Check if the node has child 
    //  */
    // public function hasChild(Node|string $node): bool;

    /**
     * Get the parent node or root configure.
     * 
     * @return Node|Root|null
     */
    public function parent(): Node|Root|null;

    /**
     * Get the root configure.
     * 
     * @return Root
     */
    public function root(): Root;

    /**
     * Check if the node is a direct child of the root configure.
     * 
     * @return bool
     */
    public function isRoot(): bool;

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
    public function isChildOf(Node|Root $node): bool;

    /**
     * Check if the node has a parent node or root.
     * 
     * @return bool
     */
    public function hasParent(): bool;

    /**
     * Set the parent node or root configure.
     * 
     * @param Node|Root $parent
     * @return self
     */
    public function setParent(Node|Root $parent): self;

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
     * @return Node|Root
     */
    public function remove(): Node|Root;

    /**
     * Convert the node to array representation.
     * 
     * @return array
     */
    public function toArray(): array;
}
