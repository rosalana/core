<?php

namespace Rosalana\Core\Services\Outpost;

class OutpostRegistry
{
    protected static array $packets = [];

    /**
     * Register a packet
     */
    public static function register(string $alias, \Closure $callback): void
    {
        self::$packets[$alias] = $callback;
    }

    /**
     * Resolve a packet
     */
    public static function resolve(string $alias): ?\Closure
    {
        return static::$packets[$alias] ?? null;
    }
}