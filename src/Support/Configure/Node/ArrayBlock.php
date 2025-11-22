<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

/**
 * Helper for Section Node
 * POZOR když je section vnitř jiné section tak ta hlavní section
 * nemá v raw kompletní obsah vnitřní section ale jen prázdné řádky.
 * 
 * Měla by to asi mít celé
 */
class ArrayBlock extends Node
{
    public function __construct(
        protected int $start,
        protected int $end,
        protected array $raw,
        protected string $key,
    ) {}

    public static function parse(array $content): Collection
    {
        $blocks = collect();
        $stack = [];

        $startRegex = '/^\s*([\'"])(?<key>[^\'"]+)\1\s*=>\s*\[\s*$/';

        foreach ($content as $index => $line) {

            $trim = trim($line);

            if (preg_match($startRegex, $line, $m)) {

                $key = $m['key'];

                $fullKey = empty($stack)
                    ? $key
                    : ($stack[array_key_last($stack)]['fullKey'] . '.' . $key);

                $stack[] = [
                    'key' => $key,
                    'fullKey' => $fullKey,
                    'start' => $index,
                    'depth' => 1,
                    'raw' => [$index => $line],
                ];

                continue;
            }

            if (!empty($stack)) {
                $stack[array_key_last($stack)]['raw'][$index] = $line;
            }

            if (!empty($stack) && $trim === '[') {
                $stack[array_key_last($stack)]['depth']++;
            }

            if (!empty($stack) && ($trim === ']' || $trim === '],')) {

                $stack[array_key_last($stack)]['depth']--;

                if ($stack[array_key_last($stack)]['depth'] === 0) {

                    $top = array_pop($stack);

                    $blocks->push(new static(
                        start: $top['start'],
                        end: $index,
                        raw: $top['raw'],
                        key: $top['fullKey']
                    ));
                }
            }
        }

        return $blocks;
    }

    public function render(): array
    {
        return $this->raw;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function isNested(): bool
    {
        return str_contains($this->key, '.');
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'key' => $this->key,
        ]);
    }
}
