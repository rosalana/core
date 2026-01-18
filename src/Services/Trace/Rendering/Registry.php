<?php

namespace Rosalana\Core\Services\Trace\Rendering;

use Rosalana\Core\Facades\Trace as FacadesTrace;
use Rosalana\Core\Services\Trace\Trace;
use Rosalana\Core\Trace\Target\Console;

class Registry
{
    /**
     * Schemes are non-abstract Target classes
     * 
     * @var array<string, array<class-string<Target>>>
     */
    protected static array $schemes = [];

    /**
     * Targets are abstract Target classes
     * 
     * @var array<string, class-string<Target>>
     */
    protected static array $targets = [
        'console' => Console::class,
    ];

    /**
     * Register multiple log schemes.
     * 
     * @param array<string, array<class-string<Target>>> $schemes
     * @return void
     */
    public static function register(array $schemes): void
    {
        foreach ($schemes as $pattern => $class) {
            self::$schemes[$pattern] = $class;
        }
    }

    /**
     * Register a target alias.
     * 
     * @param string $alias
     * @param class-string<Target> $class
     * @return void
     */
    public static function targetAlias(string $alias, string $class): void
    {
        self::$targets[$alias] = $class;
    }

    /**
     * Get all registered schemes.
     * 
     * @return array<string, class-string<Target>>
     */
    public static function schemes(): array
    {
        return self::$schemes;
    }

    /**
     * Get all registered targets.
     * 
     * @return array<string, class-string<Target>>
     */
    public static function targets(): array
    {
        return self::$targets;
    }

    /**
     * Get a registered target by name.
     * 
     * @param string|class-string<Target> $target
     * @return class-string<Target>|null
     */
    public static function getTarget(string $target): ?string
    {
        if (isset(self::$targets[$target])) {
            return self::$targets[$target];
        }

        if (class_exists($target) && is_subclass_of($target, Target::class)) {
            return $target;
        }

        return null;
    }

    /**
     * Find all implementation classes for a specific abstract target in a trace and its phases.
     * 
     * @param string $abstractTarget
     * @param Trace $trace
     * @return Target[]
     */
    private static function findImplementationClasses(string $abstractTarget, Trace $trace): array
    {
        $implementations = [];

        $implementation = self::matchImplementationClass($trace, $abstractTarget);

        if ($implementation) {
            $implementations[] = $implementation;
        }

        if ($trace->hasPhases()) {
            foreach ($trace->phases() as $phase) {
                $phaseImplementations = self::findImplementationClasses($abstractTarget, $phase);
                $implementations = array_merge($implementations, $phaseImplementations);
            }
        }

        return $implementations;
    }

    /**
     * Match an implementation class for a specific abstract target in a trace.
     * 
     * @param Trace $trace
     * @param string $abstractTarget
     * @return Target|null
     */
    private static function matchImplementationClass(Trace $trace, string $abstractTarget): Target|null
    {
        $impls = matches($trace->name())->resolve(static::schemes());

        if ($impls) {
            $impl = array_filter($impls, function ($impl) use ($abstractTarget) {
                return is_subclass_of($impl, $abstractTarget);
            })[array_key_first($impls)] ?? null;

            return $impl ? new $impl($trace) : null;
        }

        return null;
    }

    /**
     * Render Trace for specific target.
     * 
     * @param string|class-string<Target> $target
     * @param Trace $trace
     */
    public static function render(string $target, Trace $trace): void
    {
        if (! FacadesTrace::isEnabled()) return;

        $target = self::getTarget($target);
        if (! $target) return;

        $implementations = self::findImplementationClasses($target, $trace);

        foreach ($implementations as $implementation) {
            $implementation->build();
        }
    }
}
