<?php

namespace Rosalana\Core\Services\App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Rosalana\Core\Facades\App;

class Context
{
    protected string $prefix = 'context';

    /**
     * Key can be a string or e.g. [User::class, 1, 'role'];
     */
    public function get(mixed $key = 'app', mixed $default = null): mixed
    {
        [$base, $path] = $this->formatKey($key);

        $data = Cache::get($base, []);

        return Arr::get($data, $path, $default);
    }

    /**
     * Put a value into the context.
     * Key can be a string or e.g. [User::class, 1, 'role'].
     * If the key is a root context (e.g. 'app'),
     * it will throw an exception to prevent overwriting the whole context.
     */
    public function put(mixed $key, mixed $value, ?int $ttl = null): void
    {
        if (!$this->interactingWithValue($key) && !is_array($value)) {
            throw new \InvalidArgumentException("Cannot overwrite root context [{$this->formatKey($key)[0]}] directly.");
            return; // dont let user to overwrite the whole context object by mistake
        }

        if ($this->has($key)) {
            $this->merge($key, $value, $ttl);
        } else {
            $this->create($key, $value, $ttl);
        }
    }

    /**
     * Check if a key exists in the context.
     * Key can be a string or e.g. [User::class, 1, 'role'].
     */
    public function has(mixed $key): bool
    {
        [$base, $path] = $this->formatKey($key);

        if (!Cache::has($base)) return false;

        $data = Cache::get($base, []);

        return Arr::has($data, $path);
    }

    /**
     * Forget a key in the context.
     * Key can be a string or e.g. [User::class, 1, 'role'].
     * If the key is a root context (e.g. 'app'),
     * it will remove the whole context object.
     */
    public function forget(mixed $key): void
    {
        [$base, $path] = $this->formatKey($key);

        if ($this->interactingWithValue($key)) {
            $data = Cache::get($base, []);
            Arr::forget($data, $path);
            Cache::put($base, $data);
        } else {
            $data = Cache::get($base, []);
            Cache::forget($base);
        }

        App::hooks()->run('context:invalidate', [
            'key' => $base,
            'path' => $path,
            'previous' => $data,
        ]);
    }

    /**
     * Forget a key in the context.
     * Key can be a string or e.g. [User::class, 1, 'role'].
     * If the key is a root context (e.g. 'app'),
     * it will remove the whole context object.
     */
    public function invalidate(mixed $key): void
    {
        $this->forget($key);
    }

    /**
     * Flush the whole context object by its base key.
     */
    public function flush(string $group): void
    {
        $base = $this->splitKeys($group)[0];

        if (!Cache::has($base)) return;
        
        $data = Cache::get($base, []);
        Cache::forget($base);

        App::hooks()->run('context:flush', [
            'key' => $base,
            'path' => '',
            'previous' => $data,
        ]);
    }

    protected function create(mixed $key, mixed $value, ?int $ttl = null): void
    {
        [$base, $path] = $this->formatKey($key);

        $data = Cache::get($base, []);
        Arr::set($data, $path, $value);

        App::hooks()->run('context:create', [
            'key' => $base,
            'path' => $path,
            'value' => $value,
        ]);

        Cache::put($base, $data, $ttl);
    }

    protected function merge(mixed $key, mixed $partial, ?int $ttl = null): void
    {
        [$base, $path] = $this->formatKey($key);

        $data = Cache::get($base, []);
        $current = Arr::get($data, $path, []);
        $merged = is_array($current) && is_array($partial)
            ? array_merge($current, $partial)
            : $partial;

        Arr::set($data, $path, $merged);

        App::hooks()->run('context:update', [
            'key' => $base,
            'path' => $path,
            'value' => $merged,
            'previous' => $current,
        ]);

        Cache::put($base, $data, $ttl);
    }

    protected function formatKey(mixed $key): array
    {
        if (is_array($key)) {
            $key = array_map(fn($v) => $this->normalizeKeyPart($v), $key);
            return $this->splitKeys(implode('.', $key));
        }

        return $this->splitKeys($this->normalizeKeyPart($key));
    }

    protected function splitKeys(string $key): array
    {
        $segments = explode('.', $key);
        if (empty($segments[0])) {
            throw new \InvalidArgumentException("Invalid key: empty base segment.");
        }
        $base = array_shift($segments);
        return [$this->prefix . '.' . $base, implode('.', $segments)];
    }

    protected function normalizeKeyPart(mixed $part): string
    {
        // pozor možná nedělá z [User::class, 1] -> user.1
        // nebo ['user', 1] -> user.1
        // nebo 'user.1' -> user.1
        // nebo $user (instance) -> user.1
        return match (true) {
            is_string($part) && class_exists($part) => class_basename($part),
            is_string($part) => $part, // žádný slug! necháme třeba 'user.1'
            is_object($part) && method_exists($part, 'getKey') => class_basename($part) . '.' . $part->getKey(),
            is_object($part) => class_basename($part),
            is_int($part) => (string) $part,
            default => 'unknown',
        };
    }

    protected function interactingWithValue(mixed $key): bool
    {
        return (bool) $this->formatKey($key)[1] ?? null;
    }
}
