<?php

namespace Rosalana\Core\Support\Configure;

use Illuminate\Support\Collection;
use Rosalana\Core\Contracts\Configure\Node;
use Rosalana\Core\Support\Configure\Node\RichComment;
use Rosalana\Core\Support\Configure\Node\Value;

class Section
{
    protected string $name;

    protected Collection $nodes;

    public function __construct()
    {
        $this->nodes = collect();
    }

    public static function from(Collection $nodes): Collection
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

    protected static function normalize(array $tree, string $prefix = ''): Collection
    {
        $result = collect();

        foreach ($tree as $key => $value) {

            if ($value instanceof Node) {

                if ($value instanceof Value) {
                    $value->setKey($value->getKey());
                }

                $result->push($value);
                continue;
            }

            if (is_array($value)) {

                $section = new static();
                $section->name($key);

                $children = static::normalize($value, $prefix ? "$prefix.$key" : $key);

                foreach ($children as $child) {
                    $section->add($child);
                }

                $result->push($section);
            }
        }

        return $result;
    }

    public function add(Node|Section $node): self
    {
        $this->nodes->push($node);

        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
