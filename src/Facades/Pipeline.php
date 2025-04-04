<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Rosalana\Core\Pipeline\Pipeline resolve(string $alias)
 * @method static void extend(string $alias, callable $pipe)
 * @method static void forget(string $alias)
 * @method static void flush()
 * @method static array all()
 *
 * @see \Rosalana\Core\Pipeline\PipelineRegistry
 */
class Pipeline extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rosalana.pipeline.registry';
    }
}
