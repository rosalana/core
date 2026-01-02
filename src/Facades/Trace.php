<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void start(string $name, array $context = [])
 * @method static \Rosalana\Core\Services\Trace\Scope phase(string $name)
 * @method static \Rosalana\Core\Services\Trace\Trace finish()
 * @method static mixed wrap(callable $process, ?string $name = null)
 * @method static void record(mixed $data = null)
 * @method static void exception(\Throwable $exception, mixed $data = null)
 * @method static void fail(\Throwable $exception, mixed $data = null)
 * @method static array<\Rosalana\Core\Services\Trace\Trace> getTraces()
 * @method static void flush()
 * 
 * @see \Rosalana\Core\Services\Trace\Manager
 */
class Trace extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'rosalana.trace';
    }
}