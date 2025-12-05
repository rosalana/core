<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

class Section extends ParentNode
{
    protected Collection $nodes;

    public function __construct(int $start, int $end, array $raw)
    {
        parent::__construct($start, $end, $raw);
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
                    start: $meta?->startLine() ?? static::computeStart($children),
                    end: $meta?->endLine() ?? static::computeEnd($children),
                    raw: $meta?->raw() ?? []
                );

                $section->setKey($key);

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

    public function render(): array
    {
        $result = collect()
            ->push("'{$this->name()}' => [")
            ->push($this->nodes()->map(
                fn($node) => $node->render()
            ))
            ->push("],");

        $result = $this->indexRender($result);
        $lastLine = array_last($result);
        unset($result[array_key_last($result)]);
        $result[$this->end()] = $lastLine;

        return $result;
    }

    public function nodes(): Collection
    {
        return $this->nodes;
    }

    // pozor mělo by to posunout i všechny další nadcházející uzly
    // stejně tak je potřeba upravit depth podle jeho siblings
    // public function addNode(Node $node): self
    // {
    //     $node->setParent($this);

    //     if (! $node->isIndexed()) {
    //         $distance = abs($node->start() - $node->end());
    //         $lastNode = $this->nodes()->last();

    //         if ($lastNode) {
    //             $offset = $lastNode instanceof Value ? 1 : 2;
    //             $start = $lastNode->end() + $offset;
    //         } else {
    //             $start = $this->start() + 1;
    //         }

    //         $node->setStartLine($start);
    //         $node->setEndLine($start + $distance);

    //         $this->setEndLine($node->endLine() + 1);
    //     }

    //     $this->nodes->push($node);

    //     return $this;
    // }

    // public function findNode(string $key): ?Node
    // {
    //     foreach ($this->nodes as $node) {
    //         if ($node instanceof RichComment) {
    //             continue;
    //         }

    //         if ($node->key() === $key) {
    //             return $node;
    //         }
    //     }

    //     return null;
    // }

    // public function value(string $key): Value
    // {
    //     $node = $this->findNode($key);

    //     if ($node instanceof Value) {
    //         return $node;
    //     }

    //     $value = Value::makeEmpty($key);
    //     $this->addNode($value);

    //     return $value;
    // }

    /**
     * Add basic comment node.
     */
    // public function comment(string $label): RichComment
    // {
    //     return new RichComment(0, 0, [], $label, null);
    // }

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

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'nodes' => $this->nodes->map(fn($node) => $node->toArray())->toArray(),
        ]);
    }
}
