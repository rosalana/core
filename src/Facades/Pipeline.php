<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Rosalana\Core\Services\Pipeline\Pipeline resolve(string $alias)
 * @method static void extend(string $alias, callable $pipe)
 * @method static void extendIfExists(string $alias, callable $pipe)
 * @method static bool exists(string $alias)
 * @method static void forget(string $alias)
 * @method static void flush()
 * @method static array all()
 *
 * @see \Rosalana\Core\Services\Pipeline\Registry
 */
class Pipeline extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rosalana.pipeline';
    }
}
