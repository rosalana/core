<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

class ArrayBlock extends Node
{
    public function __construct(int $start, int $end, array $raw)
    {
        parent::__construct($start, $end, $raw);
    }

    public function padding(): int
    {
        return 0;
    }

    public static function parse(array $content): Collection
    {
        $blocks = collect();
        $stack = [];

        $startRegex = '/^\s*([\'"])(?<key>[^\'"]+)\1\s*=>\s*\[\s*$/';

        foreach ($content as $index => $line) {

            $trim = trim($line);

            if (preg_match($startRegex, $line, $m)) {

                $key = $m['key'];

                $nestedKey = empty($stack)
                    ? $key
                    : ($stack[array_key_last($stack)]['nestedKey'] . '.' . $key);

                $stack[] = [
                    'key' => $key,
                    'nestedKey' => $nestedKey,
                    'start' => $index,
                    'depth' => 1,
                    'raw' => [$index => $line],
                    'parent'    => empty($stack) ? null : array_key_last($stack),
                ];

                if ($nestedKey !== $key) {
                    static::propagateLineToParentBlocks($stack, $index, $line);
                }

                continue;
            }

            if (!empty($stack)) {
                static::propagateLineToParentBlocks($stack, $index, $line);
            }

            if (!empty($stack) && $trim === '[') {
                $stack[array_key_last($stack)]['depth']++;
            }

            if (!empty($stack) && ($trim === ']' || $trim === '],')) {

                $stack[array_key_last($stack)]['depth']--;

                if ($stack[array_key_last($stack)]['depth'] === 0) {

                    $top = array_pop($stack);

                    $blocks->push(ArrayBlock::make(
                        start: $top['start'],
                        end: $index,
                        raw: $top['raw'],
                    )->setKey($top['nestedKey']));
                }
            }
        }

        return $blocks;
    }

    protected static function propagateLineToParentBlocks(array &$stack, int $lineIndex, string $line): void
    {
        $current = array_key_last($stack);

        while ($current !== null) {
            $stack[$current]['raw'][$lineIndex] = $line;
            $current = $stack[$current]['parent'];
        }
    }

    public function render(): array
    {
        return $this->raw;
    }
}
