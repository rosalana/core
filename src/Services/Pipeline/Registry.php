<?php

namespace Rosalana\Core\Services\Pipeline;

class Registry
{
    /**
     * Registered pipelines by alias.
     * 
     * @var array<string, Pipeline>
     */
    protected static array $pipelines = [];

    /**
     * Get or create a pipeline by alias.
     * @param string $alias
     */
    public static function resolve(string $alias): Pipeline
    {
        if (!isset(static::$pipelines[$alias])) {
            static::$pipelines[$alias] = new Pipeline($alias);
        }

        return static::$pipelines[$alias];
    }

    /**
     * Determine if a pipeline exists.
     * @param string $alias
     */
    public static function exists(string $alias): bool
    {
        return isset(static::$pipelines[$alias]);
    }

    /**
     * Extend a pipeline safely
     * @param string $alias
     * @param callable|string $pipe (arg. ?$payload, ?$next)
     */
    public static function extend(string $alias, callable|string $pipe): void
    {
        static::resolve($alias)->extend($pipe);
    }

    /**
     * Extend a pipeline if it exists.
     * @param string $alias
     * @param callable|string $pipe (arg. ?$payload, ?$next)
     */
    public static function extendIfExists(string $alias, callable $pipe): void
    {
        if (static::exists($alias)) {
            static::extend($alias, $pipe);
        }
    }

    /**
     * Reset a pipeline (for testing or rebuilding).
     * @param string $alias
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
