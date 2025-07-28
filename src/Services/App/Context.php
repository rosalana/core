<?php

namespace Rosalana\Core\Services\App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

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

    public function has(mixed $key): bool
    {
        [$base, $path] = $this->formatKey($key);

        if (!Cache::has($base)) return false;

        $data = Cache::get($base, []);

        return Arr::has($data, $path);
    }

    public function forget(mixed $key): void
    {
        [$base, $path] = $this->formatKey($key);

        if ($this->interactingWithValue($key)) {
            $data = Cache::get($base, []);
            Arr::forget($data, $path);
            Cache::put($base, $data);
        } else {
            Cache::forget($base);
        }
    }

    public function invalidate(mixed $key): void
    {
        $this->forget($key);
    }

    public function flush(): void
    {
        Cache::forget($this->prefix . '.*');
    }

    protected function create(mixed $key, mixed $value, ?int $ttl = null): void
    {
        [$base, $path] = $this->formatKey($key);

        $data = Cache::get($base, []);
        Arr::set($data, $path, $value);

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
            is_string($part) => $part, // žádný slug! necháme třeba 'user.1'
            is_object($part) && method_exists($part, 'getKey') => class_basename($part) . '.' . $part->getKey(),
            is_object($part) => class_basename($part),
            is_int($part) => (string) $part,
            default => 'unknown',
        };
    }

    protected function interactingWithValue(mixed $key): bool
    {
        [$base, $path] = $this->formatKey($key);

        return !!$path;
    }
}
