<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(mixed $key, mixed $default = null)
 * @method static void put(mixed $key, mixed $value, ?int $ttl = null)
 * @method static bool has(mixed $key)
 * @method static void forget(mixed $key)
 * @method static void invalidate(mixed $key)
 * @method static void flush(string $group)
 * @method static array find(string $pattern, array $where = [])
 * @method static array|null findFirst(string $pattern, array $where = [])
 *
 * @see \Rosalana\Core\Services\App\Context
 */
class Context extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rosalana.context';
    }
}