<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void start(string $name, array $context = [])
 * @method static \Rosalana\Core\Services\Trace\Scope phase(string $name)
 * @method static \Rosalana\Core\Services\Trace\Trace finish(null|string|class-string<\Rosalana\Core\Services\Logging\LogRenderer> $logRenderer = null)
 * @method static mixed capture(callable $process, ?string $name = null)
 * @method static void record(mixed $data = null)
 * @method static void recordWhen(bool $condition, mixed $data = null)
 * @method static void decision(mixed $data = null)
 * @method static void decisionWhen(bool $condition, mixed $data = null)
 * @method static void recordOrDecision(bool $isDecision, mixed $data = null)
 * @method static void exception(\Throwable $exception, mixed $data = null)
 * @method static array<\Rosalana\Core\Services\Trace\Trace> getTraces()
 * @method static void flush()
 * @method static bool isEnabled()
 * @method static void registerScheme(string $pattern, class-string<\Rosalana\Core\Services\Logging\LogScheme> $class)
 * @method static void registerSchemes(array<string, class-string<\Rosalana\Core\Services\Logging\LogScheme>> $schemes)
 * @method static void registerRenderer(string $name, class-string<\Rosalana\Core\Services\Logging\LogRenderer> $class)
 * @method static void registerRenderers(array<string, class-string<\Rosalana\Core\Services\Logging\LogRenderer>> $renderers)
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