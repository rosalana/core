<?php

namespace Rosalana\Core\Services\Outpost;

class Registry
{
    protected static array $packets = [];

    /**
     * Register a packet with an alias
     * @param string $alias
     * @param \Closure $callback
     */
    public static function register(string $alias, \Closure $callback): void
    {
        self::$packets[$alias] = $callback;
    }

    /**
     * Determine if a packet exists
     * @param string $alias
     */
    public static function exists(string $alias): bool
    {
        return isset(static::$packets[$alias]);
    }

    /**
     * Resolve a packet by its alias
     * @param string $alias
     * @return \Closure|null
     */
    public static function resolve(string $alias): ?\Closure
    {
        return static::$packets[$alias] ?? null;
    }

    /**
     * Forget a packet by its alias (for testing or rebuilding)
     * @param string $alias
     */
    public static function forget(string $alias): void
    {
        unset(static::$packets[$alias]);
    }

    /**
     * Clear all registered packets
     */
    public static function flush(): void
    {
        static::$packets = [];
    }

    /**
     * Get all registered packets
     * @return array
     */
    public static function all(): array
    {
        return array_keys(static::$packets);
    }
}