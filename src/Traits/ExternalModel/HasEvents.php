<?php

namespace Rosalana\Core\Traits\ExternalModel;

trait HasEvents
{
    /** @var array<class-string, array<string, \Closure[]>> */
    protected static array $modelObservers = [];


    public static function retrieved(\Closure $callback): void
    {
        static::registerModelEvent('retrieved', $callback);
    }

    public static function creating(\Closure $callback): void
    {
        static::registerModelEvent('creating', $callback);
    }

    public static function created(\Closure $callback): void
    {
        static::registerModelEvent('created', $callback);
    }

    public static function updating(\Closure $callback): void
    {
        static::registerModelEvent('updating', $callback);
    }

    public static function updated(\Closure $callback): void
    {
        static::registerModelEvent('updated', $callback);
    }

    public static function deleting(\Closure $callback): void
    {
        static::registerModelEvent('deleting', $callback);
    }

    public static function deleted(\Closure $callback): void
    {
        static::registerModelEvent('deleted', $callback);
    }

    protected static function registerModelEvent(string $event, \Closure $callback): void
    {
        static::$modelObservers[static::class][$event][] = $callback;
    }

    protected static function fireModelEvent(string $event, self $model): void
    {
        foreach (static::$modelObservers[static::class][$event] ?? [] as $callback) {
            $callback($model);
        }
    }
}
