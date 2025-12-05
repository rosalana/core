<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;
use Rosalana\Core\Support\Configure;

class Root extends ParentNode
{
    public function __construct(protected Configure $orchestrator)
    {
        return parent::__construct(0, 0, []);
    }

    public static function parse(array $nodes): Collection
    {
        return collect();
    }

    public function render(): array
    {
        return [];
    }
    
    public function __call($name, $arguments)
    {
        if (! $this->orchestrator) {
            throw new \BadMethodCallException("Method {$name} does not exist on " . static::class);
        }

        return $this->orchestrator->$name(...$arguments);
    }

    // root is configure::class but as a node
    // configure class just use root node

    // je to další rozdělení aby se nemýchaly věci dohromady
}
