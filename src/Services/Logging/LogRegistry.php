<?php

namespace Rosalana\Core\Services\Logging;

use Rosalana\Core\Logging\Renderers\Console;
use Rosalana\Core\Logging\Renderers\File;
use Rosalana\Core\Services\Trace\Trace;

class LogRegistry
{
    /**
     * @var array<string, class-string<LogScheme>>
     */
    protected static array $schemes = [];

    /**
     * @var array<string, class-string<LogRenderer>>
     */
    protected static array $renderers = [
        'console' => Console::class,
        'file' => File::class,
    ];

    /**
     * Register log schemes.
     * 
     * @param string $pattern may include wildcards (*) and variants ({opt1|opt2})
     * @param class-string<LogScheme> $class
     * @return void
     */
    public static function registerScheme(string $pattern, string $class): void
    {
        self::$schemes[$pattern] = $class;
    }

    /**
     * Register multiple log schemes.
     * 
     * @param array<string, class-string<LogScheme>> $schemes
     * @return void
     */
    public static function registerSchemes(array $schemes): void
    {
        foreach ($schemes as $match => $class) {
            self::registerScheme($match, $class);
        }
    }

    /**
     * Get all registered log schemes.
     * 
     * @return array<class-string<LogScheme>>
     */
    public static function getSchemes(): array
    {
        return self::$schemes;
    }

    /**
     * Register a log renderer.
     * 
     * @param string $name
     * @param class-string<LogRenderer> $class
     * @return void
     */
    public static function registerRenderer(string $name, string $class): void
    {
        self::$renderers[$name] = $class;
    }

    /**
     * Register multiple log renderers.
     * 
     * @param array<string, class-string<LogRenderer>> $renderers
     * @return void
     */
    public static function registerRenderers(array $renderers): void
    {
        foreach ($renderers as $name => $class) {
            self::registerRenderer($name, $class);
        }
    }

    /**
     * Get a renderer class by its name or class string.
     * 
     * @param string|class-string<LogRenderer> $nameOrClass
     * @return class-string<LogRenderer>|null
     */
    public static function getRenderer(string $nameOrClass): ?string
    {
        if (isset(self::$renderers[$nameOrClass])) {
            return self::$renderers[$nameOrClass];
        }

        if (class_exists($nameOrClass) && is_subclass_of($nameOrClass, LogRenderer::class)) {
            return $nameOrClass;
        }

        return null;
    }

    /**
     * Render logs using the specified renderer.
     * 
     * @param string|class-string<LogRenderer> $nameOrClass
     * @param Trace $trace
     * @return void
     */
    public static function render(string $nameOrClass, Trace $trace): void
    {
        $renderer = self::getRenderer($nameOrClass);
        if (! $renderer) return;

        new $renderer($trace);
    }
}
