<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Rosalana\Core\Services\Trace\Span start(string $name, array $context = [])
 * @method static \Rosalana\Core\Services\Trace\Span end()
 * @method static \Rosalana\Core\Services\Trace\Span finish()
 * @method static mixed wrap(callable $process, ?string $name = null)
 * @method static void record(mixed $data = null)
 * @method static void fail(\Throwable $exception, mixed $data = null)
 * @method static \Rosalana\Core\Services\Trace\Span phase(string $name)
 * @method static array<\Rosalana\Core\Services\Trace\Span> getTraces()
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