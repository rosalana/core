<?php

namespace Rosalana\Core\Services\App;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Rosalana\Core\Facades\App;

class ContextStore
{
    public const MODE_SCOPED = 'scoped';
    public const MODE_GLOBAL = 'global';

    public const ARRAY_MARKER = '__array';
    public const INDEX_SUFFIX = '__index';

    protected string $mode;

    protected string $appId;
    protected \Redis $redis;

    protected ?string $scope = null;

    /**
     * Create a scoped store bound to a specific scope.
     */
    public static function scoped(string $scope): self
    {
        $i = new self(mode: self::MODE_SCOPED);
        $i->scope = $i->normalizeScope($scope);

        return $i;
    }

    /**
     * Create a global store for cross-scope operations.
     */
    public static function global(): self
    {
        return new self(mode: self::MODE_GLOBAL);
    }

    /**
     * Create a store instance.
     */
    protected function __construct(string $mode)
    {
        $this->mode = $mode;

        $this->appId = (string) App::id();

        if ($this->appId === '' || $this->appId === 'app-id') {
            throw new \RuntimeException('App ID is not set. Cannot initialize ContextStore.');
        }

        $client = Redis::connection()->client();

        if (!($client instanceof \Redis)) {
            throw new \RuntimeException('Redis client is not a phpredis instance.');
        }

        $this->redis = $client;
    }

    /**
     * Put a value into the current scope. Arrays are stored using explicit array markers.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->requireScoped();

        $this->putNode($key, $value, $ttl);
    }

    /**
     * Get a value from the current scope. Returns scalar leaf if present, otherwise reconstructs a subtree.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->requireScoped();

        $leafKey = $this->key($key);

        $raw = $this->redis->get($leafKey);

        if ($raw !== false && $raw !== null) {
            return $this->decode($raw);
        }

        $tree = $this->dumpSubtree($key);

        return empty($tree) ? $default : $tree;
    }

    /**
     * Check whether a path exists as a leaf or subtree in the current scope.
     */
    public function has(string $key): bool
    {
        $this->requireScoped();

        $leafKey = $this->key($key);

        if ($this->redis->exists($leafKey)) {
            return true;
        }

        $prefix = $leafKey . ':';

        foreach ($this->indexes() as $k) {
            if (!str_starts_with($k, $prefix)) {
                continue;
            }

            if ($this->redis->exists($k)) {
                return true;
            }

            $this->removeIndex($k);
        }

        return false;
    }

    /**
     * Forget a leaf or subtree in the current scope.
     */
    public function forget(string $key): void
    {
        $this->requireScoped();

        $this->deleteNode($key);

        $this->maybeUnregisterScope();
    }

    /**
     * Dump the whole scope as a reconstructed document tree.
     */
    public function receive(): array
    {
        $this->requireScoped();

        return $this->dumpSubtree('');
    }

    /**
     * Clear the whole scope (all values and its index).
     */
    public function clear(): void
    {
        $this->requireScoped();

        $index = $this->indexKey();
        $keys = $this->redis->sMembers($index) ?: [];

        if (!empty($keys)) {
            $this->redis->del(...$keys);
        }

        $this->redis->del($index);

        $this->unregisterScope();
    }

    /**
     * Find values inside the current scope using wildcard pattern and optional where constraints.
     */
    public function find(string $pattern, array $where = [], ?int $limit = null): array
    {
        if ($this->isGlobal()) {
            return $this->findGlobal($pattern, $where, $limit);
        }

        $this->requireScoped();

        $pattern = $this->normalizePattern($pattern);

        if (!str_contains($pattern, '*')) {
            $value = $this->get($pattern, null);

            if ($value === null) {
                return [];
            }

            if (!$this->matchesWhere($value, $where)) {
                return [];
            }

            return [$pattern => $value];
        }

        $results = [];
        $limit = $limit !== null && $limit > 0 ? $limit : null;

        foreach ($this->indexes() as $k) {
            if (!$this->redis->exists($k)) {
                $this->removeIndex($k);
                continue;
            }

            $dot = $this->relativeDotPathFromRedisKey($k);

            if ($this->isArrayMarkerDotPath($dot)) {
                continue;
            }

            if (!$this->matchesWildcard($pattern, $dot)) {
                continue;
            }

            $value = $this->decode((string) $this->redis->get($k));

            if (!$this->matchesWhere([$dot => $value], $where)) {
                continue;
            }

            $results[$dot] = $value;

            if ($limit !== null && count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    /**
     * List all scopes for the current App ID.
     */
    public function all(): array
    {
        $this->requireGlobal();

        $scopes = $this->redis->sMembers($this->globalIndexKey()) ?: [];
        $scopes = array_values(array_unique(array_filter(array_map('strval', $scopes))));

        sort($scopes);

        $result = [];

        foreach ($scopes as $scope) {
            if ($scope === '') {
                continue;
            }

            $scoped = self::scoped($scope);
            $result[$scope] = $scoped->receive();
        }

        return $result;
    }

    public function raw(): array
    {
        $this->requireGlobal();

        $pattern = '*' . $this->basePrefix() . '*';
        $keys = $this->redis->keys($pattern);

        $keys = array_map(function ($k) {
            return explode($this->basePrefix(), $k, 2)[1] ?? '';
        }, $keys);

        if (empty($keys)) {
            return [];
        }

        $result = [];
        foreach ($keys as $key) {

            if ($this->redis->type($this->basePrefix() . $key) === \Redis::REDIS_SET) {
                $value = $this->redis->sMembers($this->basePrefix() . $key);
            } else {
                $value = $this->redis->get($this->basePrefix() . $key);
            }

            if ($value !== false) {
                $result[$this->basePrefix() . $key] = $value;
            }
        }

        return $result;
    }

    /**
     * Flush all context data for the current App ID.
     */
    public function flush(): int
    {
        $this->requireGlobal();

        $deleted = 0;

        foreach (array_keys($this->all()) as $scope) {
            $store = self::scoped($scope);
            $deleted += $store->estimatedKeyCount();
            $store->clear();
        }

        $this->redis->del($this->globalIndexKey());

        return $deleted;
    }

    /**
     * Find values across scopes using a scope-aware pattern and optional where constraints.
     */
    protected function findGlobal(string $pattern, array $where = [], ?int $limit = null): array
    {
        $this->requireGlobal();

        [$scopePattern, $pathPattern] = $this->parseGlobalPattern($pattern);

        $results = [];
        $limit = $limit !== null && $limit > 0 ? $limit : null;

        foreach (array_keys($this->all()) as $scope) {
            if (!Str::is($scopePattern, $scope)) {
                continue;
            }

            $store = self::scoped($scope);
            $found = $store->find($pathPattern === '' ? '*' : $pathPattern, $where, $limit);

            foreach ($found as $path => $value) {
                $results[$scope] = $store->receive();

                if ($limit !== null && count($results) >= $limit) {
                    break 2;
                }
            }
        }

        return $results;
    }

    /**
     * Parse a scope-aware pattern into (scopePattern, pathPattern).
     */
    protected function parseGlobalPattern(string $pattern): array
    {
        $pattern = $this->normalizePattern($pattern);

        $segments = explode('.', $pattern);
        $base = array_shift($segments);

        if ($base === Context::DEFAULT_SCOPE) {
            return [Context::DEFAULT_SCOPE, implode('.', $segments)];
        }

        if (isset($segments[0]) && ($segments[0] === '*' || ctype_digit($segments[0]))) {
            $scopePattern = $base . '.' . $segments[0];
            array_shift($segments);

            return [$scopePattern, implode('.', $segments)];
        }

        return [$base, implode('.', $segments)];
    }

    /**
     * Store a node value (scalar or array). Arrays create an explicit array marker.
     */
    protected function putNode(string $path, mixed $value, ?int $ttl): void
    {
        if (is_array($value)) {
            $this->deleteNode($path);

            $markerPath = $path . '.' . self::ARRAY_MARKER;
            $markerKey = $this->key($markerPath);
            $markerValue = array_is_list($value) ? 'list' : 'assoc';

            if ($ttl !== null) {
                $this->redis->set($markerKey, $markerValue, ['ex' => $ttl]);
            } else {
                $this->redis->set($markerKey, $markerValue);
            }

            $this->addIndex($markerKey);

            foreach ($value as $k => $v) {
                $child = $path . '.' . (string) $k;
                $this->putNode($child, $v, $ttl);
            }

            return;
        }

        $this->deleteChildrenOnly($path);

        $leafKey = $this->key($path);
        $payload = $this->encode($value);

        if ($ttl !== null) {
            $this->redis->set($leafKey, $payload, ['ex' => $ttl]);
        } else {
            $this->redis->set($leafKey, $payload);
        }

        $this->addIndex($leafKey);
    }

    /**
     * Delete a node including its leaf and its entire subtree (markers included).
     */
    protected function deleteNode(string $path): void
    {
        $leafKey = $this->key($path);

        if ($this->redis->exists($leafKey)) {
            $this->redis->del($leafKey);
            $this->removeIndex($leafKey);
        }

        $prefix = $leafKey . ':';

        foreach ($this->indexes() as $k) {
            if (!str_starts_with($k, $prefix)) {
                continue;
            }

            if ($this->redis->exists($k)) {
                $this->redis->del($k);
            }

            $this->removeIndex($k);
        }
    }

    /**
     * Delete only the children subtree of a node while preserving the leaf key overwrite semantics.
     */
    protected function deleteChildrenOnly(string $path): void
    {
        $leafKey = $this->key($path);
        $prefix = $leafKey . ':';

        foreach ($this->indexes() as $k) {
            if (!str_starts_with($k, $prefix)) {
                continue;
            }

            if ($this->redis->exists($k)) {
                $this->redis->del($k);
            }

            $this->removeIndex($k);
        }
    }

    /**
     * Dump a subtree rooted at a path and reconstruct arrays using markers.
     */
    protected function dumpSubtree(string $path): array
    {
        $path = trim($path, '.');

        $rootKeyPrefix = $path === '' ? $this->scopePrefix() . ':' : $this->key($path) . ':';
        $rootDotPrefix = $path === '' ? '' : $path . '.';

        $data = [];
        $arrays = [];

        foreach ($this->indexes() as $k) {
            if (!str_starts_with($k, $rootKeyPrefix)) {
                continue;
            }

            if (!$this->redis->exists($k)) {
                $this->removeIndex($k);
                continue;
            }

            $dot = $this->relativeDotPathFromRedisKey($k);

            if ($rootDotPrefix !== '' && str_starts_with($dot, $rootDotPrefix)) {
                $dot = substr($dot, strlen($rootDotPrefix));
            } elseif ($rootDotPrefix !== '') {
                continue;
            }

            if ($this->isArrayMarkerDotPath($dot)) {
                $node = substr($dot, 0, -strlen('.' . self::ARRAY_MARKER));
                $arrays[$node] = (string) $this->redis->get($k);
                continue;
            }

            $value = $this->decode((string) $this->redis->get($k));
            Arr::set($data, $dot, $value);
        }

        return $this->applyArrayMetadata($data, $arrays);
    }

    /**
     * Convert array nodes according to marker metadata and ensure empty arrays are preserved.
     */
    protected function applyArrayMetadata(array $data, array $arrays): array
    {
        if (empty($arrays)) {
            return $data;
        }

        uksort($arrays, function (string $a, string $b): int {
            return substr_count($b, '.') <=> substr_count($a, '.');
        });

        foreach ($arrays as $path => $type) {
            $path = trim($path, '.');

            $node = $path === '' ? $data : Arr::get($data, $path, []);
            $node = is_array($node) ? $node : [];

            if ($type === 'list') {
                ksort($node);
                $node = array_values($node);
            }

            if ($path === '') {
                $data = $node;
            } else {
                Arr::set($data, $path, $node);
            }
        }

        return $data;
    }

    /**
     * Register current scope in the global scopes index.
     */
    protected function registerScope(): void
    {
        $this->requireScoped();
        $this->redis->sAdd($this->globalIndexKey(), (string) $this->scope);
    }

    /**
     * Unregister current scope from the global scopes index.
     */
    protected function unregisterScope(): void
    {
        $this->requireScoped();
        $this->redis->sRem($this->globalIndexKey(), (string) $this->scope);
    }

    /**
     * Unregister current scope if it has no indexed keys left.
     */
    protected function maybeUnregisterScope(): void
    {
        $this->requireScoped();

        $count = (int) $this->redis->sCard($this->indexKey());

        if ($count <= 0) {
            $this->unregisterScope();
        }
    }

    /**
     * Build a redis key for a given dot-path.
     */
    protected function key(string $path): string
    {
        $path = trim($path, '.');

        if ($path === '') {
            throw new \InvalidArgumentException('Path cannot be empty for scoped key.');
        }

        return $this->scopePrefix() . ':' . str_replace('.', ':', $path);
    }

    /**
     * Get the redis key for the scope index.
     */
    protected function indexKey(): string
    {
        return $this->scopePrefix() . ':' . self::INDEX_SUFFIX;
    }

    /**
     * Get the redis key for the global scopes index.
     */
    protected function globalIndexKey(): string
    {
        return $this->basePrefix() . self::INDEX_SUFFIX;
    }

    /**
     * Return a normalized scope prefix.
     */
    protected function scopePrefix(): string
    {
        $this->requireScoped();

        return $this->basePrefix() . $this->scope;
    }

    /**
     * Get the base prefix for all context keys.
     */
    protected function basePrefix(): string
    {
        return "rosalana:{$this->appId}:ctx:";
    }

    /**
     * Return all indexed keys for the current scope.
     */
    protected function indexes(): array
    {
        $this->requireScoped();

        return $this->redis->sMembers($this->indexKey()) ?: [];
    }

    /**
     * Add a key to the scope index and ensure scope is registered globally.
     */
    protected function addIndex(string $key): void
    {
        $this->requireScoped();

        $this->registerScope();
        $this->redis->sAdd($this->indexKey(), $key);
    }

    /**
     * Remove a key from the scope index.
     */
    protected function removeIndex(string $key): void
    {
        $this->requireScoped();

        $this->redis->sRem($this->indexKey(), $key);
    }

    /**
     * Convert a full redis key into a dot-path relative to the scope root.
     */
    protected function relativeDotPathFromRedisKey(string $redisKey): string
    {
        $needle = $this->scopePrefix() . ':';
        $pos = strpos($redisKey, $needle);

        if ($pos === false) {
            return str_replace(':', '.', $redisKey);
        }

        $relative = substr($redisKey, $pos + strlen($needle));

        return str_replace(':', '.', $relative);
    }

    /**
     * Check whether a dot-path represents an array marker.
     */
    protected function isArrayMarkerDotPath(string $dotPath): bool
    {
        return str_ends_with($dotPath, '.' . self::ARRAY_MARKER);
    }

    /**
     * Return an estimated number of keys to be removed by flushing the scope.
     */
    protected function estimatedKeyCount(): int
    {
        $this->requireScoped();

        $keys = $this->redis->sMembers($this->indexKey()) ?: [];
        return count($keys) + 1;
    }

    /**
     * Normalize a scope string.
     */
    protected function normalizeScope(string $scope): string
    {
        $scope = trim(strtolower($scope));

        if ($scope === '') {
            throw new \InvalidArgumentException('Scope cannot be empty.');
        }

        if (str_contains($scope, ':')) {
            throw new \InvalidArgumentException('Scope cannot contain ":" characters.');
        }

        return $scope;
    }

    /**
     * Normalize a wildcard pattern for dot paths.
     */
    protected function normalizePattern(string $pattern): string
    {
        $pattern = trim(strtolower($pattern), '.');

        if ($pattern === '') {
            throw new \InvalidArgumentException('Pattern cannot be empty.');
        }

        if (str_contains($pattern, ':')) {
            throw new \InvalidArgumentException('Pattern cannot contain ":" characters.');
        }

        return $pattern;
    }

    /**
     * Encode a scalar value.
     */
    protected function encode(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Decode a scalar value.
     */
    protected function decode(string $payload): mixed
    {
        return json_decode($payload, true);
    }

    /**
     * Match wildcard patterns against dot paths.
     */
    protected function matchesWildcard(string $pattern, string $value): bool
    {
        $pattern = strtolower(trim($pattern));
        $value = strtolower(trim($value));

        return Str::is($pattern, $value);
    }

    /**
     * Check whether a value matches all where constraints.
     */
    protected function matchesWhere(mixed $value, array $where): bool
    {
        if (empty($where)) {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        foreach ($where as $k => $expected) {
            $key = (string) $k;

            if (!Arr::has($value, $key)) {
                return false;
            }

            if (Arr::get($value, $key) !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ensure the store is in scoped mode.
     */
    protected function requireScoped(): void
    {
        if ($this->mode !== self::MODE_SCOPED || $this->scope === null) {
            throw new \RuntimeException('This operation is only available in scoped mode.');
        }
    }

    /**
     * Ensure the store is in global mode.
     */
    protected function requireGlobal(): void
    {
        if ($this->mode !== self::MODE_GLOBAL) {
            throw new \RuntimeException('This operation is only available in global mode.');
        }
    }

    /**
     * Check if store is in global mode.
     */
    protected function isGlobal(): bool
    {
        return $this->mode === self::MODE_GLOBAL;
    }
}
