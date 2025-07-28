<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(mixed $key, mixed $default = null)
 * @method static void put(mixed $key, mixed $value, ?int $ttl = null)
 * @method static bool has(mixed $key)
 * @method static void forget(mixed $key)
 * @method static void invalidate(mixed $key)
 */
class Context extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rosalana.context';
    }
}