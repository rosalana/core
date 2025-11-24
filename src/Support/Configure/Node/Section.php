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

            if ($node instanceof ArrayBlock) {
                $parts = explode('.', $node->key());
                $current = &$tree;

                foreach ($parts as $i => $segment) {
                    if ($i === count($parts) - 1) {
                        if (!isset($current[$segment]) || !is_array($current[$segment])) {
                            $current[$segment] = [];
                        }

                        $current[$segment]['__meta'] = $node;
                    } else {
                        if (!isset($current[$segment]) || !is_array($current[$segment])) {
                            $current[$segment] = [];
                        }

                        $current = &$current[$segment];
                    }
                }

                unset($current);
                continue;
            }

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
                    $value->setKey($value->originalKey());
                }

                $result->push($value);
                continue;
            }

            if (is_array($value)) {
                $meta = $value['__meta'] ?? null;
                unset($value['__meta']);

                $children = static::normalize($value);

                $section = new static(
                    start: $meta?->startLine() ?? static::computeStart($children),
                    end: $meta?->endLine() ?? static::computeEnd($children),
                    raw: $meta?->raw() ?? []
                );

                $section->setName($key);

                foreach ($children as $child) {
                    $section->addNode($child);
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

    public function addNode(Node $node): self
    {
        $this->nodes->push($node);

        return $this;
    }

    public function nodes(): Collection
    {
        return $this->nodes;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function render(): array
    {
        return [];
    }
}
