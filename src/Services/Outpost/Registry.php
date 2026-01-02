<?php

namespace Rosalana\Core\Services\Outpost;

class Registry
{
    protected static array $listeners = [];

    public static function register(string $namespace, \Closure $callback): void
    {
        if (! static::validateNamespace($namespace)) return;

        $listener = new RegistryListener($callback);
        static::$listeners[$namespace][] = $listener;
    }

    public static function registerSilent(string $namespace, \Closure $callback): void
    {
        if (! static::validateNamespace($namespace)) return;

        $listener = new RegistryListener($callback);
        $listener->setSilent(true);
        static::$listeners[$namespace][] = $listener;
    }

    public static function get(string $namespace): array
    {
        return static::$listeners[$namespace] ?? [];
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

        if (static::exists($namespace)) {
            foreach (static::get($namespace) as $listener) {

                try {
                    $listener->handle($message);
                } catch (\Throwable $e) {
                    if (! $listener->isSilent()) {
                        throw $e;
                    }
                }

                if (! $listener->isSilent()) {
                    $consumed = true;
                }
            }
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
