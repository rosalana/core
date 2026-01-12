<?php

namespace Rosalana\Core\Services\Outpost;

use Rosalana\Core\Facades\Trace;

class Registry
{
    protected static array $listeners = [];

    public static function register(string $namespace, \Closure $callback, string $name = 'unnamed'): void
    {
        if (! static::validateNamespace($namespace)) return;

        $listener = new RegistryListener($callback, $name);
        static::$listeners[$namespace][] = $listener;
    }

    public static function registerSilent(string $namespace, \Closure $callback, string $name = 'unnamed'): void
    {
        if (! static::validateNamespace($namespace)) return;

        $listener = new RegistryListener($callback, $name);
        $listener->setSilent(true);
        static::$listeners[$namespace][] = $listener;
    }

    public static function get(string $namespace): array
    {
        return matches($namespace)->resolve(static::$listeners) ?? [];
    }

    public static function exists(string $namespace): bool
    {
        return !empty(static::get($namespace));
    }

    public static function all(): array
    {
        return array_keys(static::$listeners);
    }

    public static function forget(string $namespace): void
    {
        unset(static::$listeners[$namespace]);
    }

    public static function flush(): void
    {
        static::$listeners = [];
    }

    public static function run(Message $message): bool
    {
        $namespace = $message->namespace;

        $consumed = false;
        $shouldThrow = false;

        if (static::exists($namespace)) {
            Trace::capture(function () use ($namespace, $message, &$consumed, &$shouldThrow) {
                foreach (static::get($namespace) as $listener) {
                    try {
                        $listener->handle($message);
                    } catch (\Throwable $e) {
                        if (! $listener->isSilent()) {
                            $shouldThrow = true;
                        }
                    }

                    if (! $listener->isSilent()) {
                        $consumed = true;
                    }
                }
            }, 'Outpost:handler:registry');
        }

        if ($shouldThrow) {
            throw new \Rosalana\Core\Exceptions\Service\Outpost\OutpostException($message, "One or more registry listeners for '{$namespace}' failed.");
        }

        return $consumed;
    }

    protected static function validateNamespace(string $namespace): bool
    {
        if (! is_string($namespace) || ! preg_match('/^[a-z]+\\.[a-z]+:[a-z]+$/', $namespace) || ! in_array(explode(':', $namespace, 2)[1], Message::ALLOWED_STATUSES)) {
            return false;
        }

        return true;
    }
}
