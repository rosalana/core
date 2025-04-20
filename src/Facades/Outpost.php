<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for interacting with Rosalana Outpost (global async queue system).
 * 
 * @method static \Rosalana\Core\Services\Outpost\Manager to(string $name)
 * @method static \Rosalana\Core\Services\Outpost\Manager send(string $alias, array $payload = [])
 * @method static \Rosalana\Core\Services\Outpost\Manager receive(string $alias, \Closure $callback)
 * @method static \Rosalana\Core\Services\Outpost\Manager reset()
 * 
 * @see \Rosalana\Core\Services\Outpost\Manager
 */
class Outpost extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rosalana.outpost';
    }
}
