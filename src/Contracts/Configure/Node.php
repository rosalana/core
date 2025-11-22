<?php

namespace Rosalana\Core\Contracts\Configure;

use Illuminate\Support\Collection;

/**
 * Protože laravel config soubory v sobě mají komentáře i uvnitř polí,
 * je potřeba číst soubor jako text a parsovat ho na jednotlivé uzly.
 * 
 * Základní uzel je ValueNode což je prostě 'key' => 'string',
 * 
 * Další uzel je CommentNode což je komentář.
 * 
 * Ignorujeme tedy ty rodičovské klíče a hledáme pouze key-value páry a komentáře.
 * 
 * Respektive každý Node dostane k dispozici content aby si tam vybral sám sebe.
 * 
 * Rozparsovany config potom může vypadat takto:
 * 
 * [
 *     CommentNode,
 *     [
 *         ValueNode,
 *         ValueNode,
 *         ValueNode,
 *         [
 *             ValueNode,
 *             ValueNode
 *         ],
 *         CommentNode,
 *         ValueNode,
 *         ValueNode,
 *         ValueNode,
 *         ValueNode,
 *     ],
 *     CommentNode,
 *     [
 *         ValueNode,
 *         ValueNode,
 *         ValueNode,
 *         ValueNode,
 *         ValueNode,
 *         ValueNode,
 *     ],
 * ]
 *
 * Upgrade může být převést všechny ty arrays jako Section.
 * Section v sobě bude držet ten obsah (pole Nodes nebo Sections) a může mít svůj CommentNode (ten co je nad ní).
 * Pak by rozparsovaný soubor byl jen pole Sections.
 * 
 * Snažíme se používat Collection všude místo polí.
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

    /**
     * Convert the node to array representation.
     * 
     * @return array
     */
    public function toArray(): array;
}
