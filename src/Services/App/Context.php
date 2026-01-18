<?php

namespace Rosalana\Core\Services\App;

use Rosalana\Core\Services\App\ContextStore as Store;

/**
 * @method void put(string|array $key, mixed $value, ?int $ttl = null)
 * @method void increment(string|array $key, int $by = 1)
 * @method void decrement(string|array $key, int $by = 1)
 * @method void push(string $key, mixed $value)
 * @method mixed shift(string $key)
 * @method mixed pop(string $key)
 * @method mixed get(string|array $key, mixed $default = null)
 * @method bool has(string|array $key)
 * @method void forget(string|array $key)
 * @method array receive()
 * @method void clear()
 */
class Context
{
    public const DEFAULT_SCOPE = '__app';

    /**
     * Create a scoped store for context operations.
     */
    public function scope(mixed $scope = self::DEFAULT_SCOPE): Store
    {
        return Store::scoped($this->extractScope($scope));
    }

    /**
     * Create a global store for cross-scope operations.
     */
    public function global(): Store
    {
        return Store::global();
    }

    /**
     * List all scopes for the current App ID.
     */
    public function all(): array
    {
        return $this->global()->all();
    }

    /**
     * Retrieve raw redis context data for the current App ID.
     */
    public function raw(): array
    {
        return $this->global()->raw();
    }

    /**
     * Clear all context data for the current App ID.
     */
    public function flush(): int
    {
        return $this->global()->flush();
    }

    /**
     * Find values across all scopes using a wildcard pattern and optional where constraints.
     */
    public function find(string $pattern, array $where = [], ?int $limit = null): array
    {
        return $this->global()->find($pattern, $where, $limit);
    }

    /**
     * Proxy store methods to the default (_app) scope.
     */
    public function __call(string $name, array $arguments)
    {
        $store = $this->scope(self::DEFAULT_SCOPE);

        if (method_exists($store, $name)) {
            return $store->$name(...$arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist.");
    }

    /**
     * Extract a normalized scope identifier from a mixed input.
     */
    protected function extractScope(mixed $input): string
    {
        $raw = $this->normalizeToString($input);
        $segments = explode('.', $raw);

        if ($segments[0] === self::DEFAULT_SCOPE) {
            return self::DEFAULT_SCOPE;
        }

        $base = array_shift($segments);

        if (isset($segments[0]) && ctype_digit($segments[0])) {
            return $base . '.' . $segments[0];
        }

        return $base;
    }

    /**
     * Normalize mixed input to a dot-string representation.
     */
    protected function normalizeToString(mixed $input): string
    {
        if (is_array($input)) {
            $input = implode('.', array_map(
                fn($v) => $this->normalizeKeyPart($v),
                $input
            ));
        } else {
            $input = $this->normalizeKeyPart($input);
        }

        $input = trim($input);

        if ($input === '') {
            throw new \InvalidArgumentException('Context scope cannot be empty.');
        }

        if (str_contains($input, ':')) {
            throw new \InvalidArgumentException('Context scope cannot contain ":" characters.');
        }

        return strtolower($input);
    }

    /**
     * Normalize a single path part.
     */
    protected function normalizeKeyPart(mixed $part): string
    {
        return match (true) {
            is_string($part) && class_exists($part) => strtolower(class_basename($part)),
            is_string($part) => strtolower($part),
            is_object($part) && method_exists($part, 'getKey') => strtolower(class_basename($part) . '.' . (string) $part->getKey()),
            is_object($part) => strtolower(class_basename($part)),
            is_int($part) => (string) $part,
            default => '__unknown',
        };
    }
}
