<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

class Section extends ParentNode
{
    protected Collection $nodes;

    public static function wrap(Collection $nodes): Collection
    {
        $tree = [];
        $nodes = $nodes->sortBy->start()->values()->toArray();

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
        }

        return static::normalize($tree);
    }

    public function padding(): int
    {
        return 1;
    }

    public static function makeEmpty(string $key): static
    {
        $instance = parent::makeEmpty($key);
        $instance->end += 1; // for closing bracket
        return $instance;
    }

    public function render(): array
    {
        $result = [];

        $result[$this->start()] = "'{$this->name()}' => [";

        $this->nodes()->each(function ($node) use (&$result) {
            $rendered = $node->render();
            foreach ($rendered as $index => $line) {
                $result[$index] = $line;
            }
        });

        $result[$this->end()] = "],";

        $minKey = min(array_keys($result));

        foreach ($result as $index => $line) {
            if (is_array($line)) continue;

            $result[$index] = str_repeat(' ', $this->parent()?->indent() ?? 0) . $line;

            if (!array_key_exists($minKey, $result)) {
                $result[$minKey] = '';
            }

            $minKey++;
        }

        ksort($result);

        return $result;
    }

    public function section(string $key): Section
    {
        if (str_contains($key, '.') && $this !== $this->root()) {
            return $this->root()->section($key);
        }

        return parent::section($key);
    }

    public function value(string $key, ?string $addValue = null): Value
    {
        if (str_contains($key, '.') && $this !== $this->root()) {
            return $this->root()->value($key, $addValue);
        }

        return parent::value($key, $addValue);
    }

    /**
     * Create a rich or simple comment node.
     * If description is provided, a rich comment is created.
     * Otherwise, a simple comment is created.
     */
    public function withComment(string $label, ?string $description = null): Node
    {
        if ($description) {
            return new RichComment(0, 0, [], $label, $description);
        }

        return new RichComment(0, 0, [], $label, null); // for now
    }

    public static function normalize(array $tree): Collection
    {
        $result = collect();

        foreach ($tree as $key => $value) {

            if ($value instanceof Node && !($value instanceof Section)) {

                $value->setKey($value->name());

                $result->push($value);
                continue;
            }

            if (is_array($value)) {
                $meta = $value['__meta'] ?? null;
                unset($value['__meta']);

                $children = static::normalize($value);

                $section = Section::make(
                    start: $meta?->start() ?? static::computeStart($children),
                    end: $meta?->end() ?? static::computeEnd($children),
                    raw: $meta?->raw() ?? []
                );

                $section->setKey($key);

                foreach ($children as $child) {
                    $section->addChild($child, true);
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
                    : $node->start()
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
}
