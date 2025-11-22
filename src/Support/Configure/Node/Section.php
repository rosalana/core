<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

class Section extends Node
{
    protected string $name;

    protected Collection $nodes;

    public function __construct(protected int $start, protected int $end, protected array $raw)
    {
        $this->nodes = collect();
    }

    public static function parse(array $nodes): Collection
    {
        $tree = [];

        foreach ($nodes as $node) {

            if ($node instanceof Value) {
                $parts = explode('.', $node->key());
                $current = &$tree;

                foreach ($parts as $i => $segment) {
                    if ($i === count($parts) - 1) {
                        $current[$segment] = $node;
                    } else {
                        if (!isset($current[$segment]) || !is_array($current[$segment])) {
                            $current[$segment] = [];
                        }
                        $current = &$current[$segment];
                    }
                }

                unset($current);
            }

            if ($node instanceof RichComment) {
                $tree[] = $node;
            }
        }

        return static::normalize($tree);
    }

    public static function normalize(array $tree): Collection
    {
        $result = collect();

        foreach ($tree as $key => $value) {

            if ($value instanceof Node && !($value instanceof Section)) {

                if ($value instanceof Value) {
                    $value->setKey($value->getKey());
                }

                $result->push($value);
                continue;
            }

            if (is_array($value)) {

                $children = static::normalize($value);

                $section = new static(
                    start: static::computeStart($children),
                    end: static::computeEnd($children),
                    raw: []
                );

                $section->name($key);

                foreach ($children as $child) {
                    $section->add($child);
                }

                $result->push($section);
            }
        }

        return $result;
    }

    protected static function computeStart(Collection $children): int
    {
        $starts = $children
            ->map(
                fn($node) => $node instanceof Section
                    ? static::computeStart($node->nodes())
                    : $node->startLine()
            )
            ->filter(fn($v) => $v > 0);

        return $starts->min() ?? 0;
    }

    protected static function computeEnd(Collection $children): int
    {
        $ends = $children
            ->map(
                fn($node) => $node instanceof Section
                    ? static::computeEnd($node->nodes())
                    : $node->endLine()
            )
            ->filter(fn($v) => $v > 0);

        return $ends->max() ?? 0;
    }

    public function add(Node $node): self
    {
        $this->nodes->push($node);

        return $this;
    }

    public function nodes(): Collection
    {
        return $this->nodes;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function render(): array
    {
        return [];
    }
}
