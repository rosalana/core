<?php

namespace Rosalana\Core\Pipeline;

class PipelineRegistry
{
    /**
     * Registered pipelines by alias.
     * 
     * @var array<string, Pipeline>
     */
    protected static array $pipelines = [];

    /**
     * Get or create a pipeline by alias.
     */
    public static function resolve(string $alias): Pipeline
    {
        if (!isset(static::$pipelines[$alias])) {
            static::$pipelines[$alias] = new Pipeline($alias);
        }

        return static::$pipelines[$alias];
    }

    /**
     * Extend a pipeline safely
     */
    public static function extend(string $alias, callable $pipe): void
    {
        static::resolve($alias)->extend($pipe);
    }

    /**
     * Reset a pipeline (for testing or rebuilding).
     */
    public static function forget(string $alias): void
    {
        unset(static::$pipelines[$alias]);
    }

    /**
     * Clear all pipelines (e.g. in teardown).
     */
    public static function flush(): void
    {
        static::$pipelines = [];
    }

    /**
     * Get all aliases.
     */
    public static function all(): array
    {
        return array_keys(static::$pipelines);
    }
}