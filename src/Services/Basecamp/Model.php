<?php

namespace Rosalana\Core\Services\Basecamp;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rosalana\Core\Contracts\Basecamp\Model\ReadableExternalModel;
use Rosalana\Core\Contracts\Basecamp\Model\RemoveableExternalModel;
use Rosalana\Core\Contracts\Basecamp\Model\WritableExternalModel;

abstract class Model
{
    /** @var array<string, bool> */
    protected static array $booted = [];

    /** @var array<class-string, array<string, \Closure[]>> */
    protected static array $modelObservers = [];

    protected string $identifier = 'id';

    protected array $attributes = [];

    protected array $original = [];

    protected array $casts = [];

    protected array $appends = [];

    protected bool $timestamps = true;

    protected static function provider(): object
    {
        return app('rosalana.basecamp')->{Str::plural(Str::camel(class_basename(static::class)))}();
    }

    public function __construct(array $attributes = [])
    {
        static::bootIfNotBooted();
        $this->fill($attributes);
    }

    // --- Boot lifecycle ---

    protected static function bootIfNotBooted(): void
    {
        if (isset(static::$booted[static::class])) {
            return;
        }

        static::$booted[static::class] = true;
        static::boot();
        static::booted();
    }

    protected static function boot(): void {}

    protected static function booted(): void {}

    // --- Event registration ---

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

    // --- Static API ---

    public static function find(string|int $id): ?static
    {
        if (! static::provider() instanceof ReadableExternalModel) {
            abort(500, 'Model is not read-accessible and cannot be queried.');
        }

        try {
            return static::findOrFail($id);
        } catch (\Exception) {
            return null;
        }
    }

    /** @throws \Exception */
    public static function findOrFail(string|int $id): static
    {
        if (! static::provider() instanceof ReadableExternalModel) {
            abort(500, 'Model is not read-accessible and cannot be queried.');
        }

        try {
            $model = (new static(static::provider()->find($id)->json('data') ?? []))->syncOriginal();
        } catch (\Exception $e) {
            abort(404, "Model not found for identifier `{$id}`.", ['error' => $e->getMessage()]);
        }

        static::fireModelEvent('retrieved', $model);

        return $model;
    }

    public static function all(): Collection
    {
        if (! static::provider() instanceof ReadableExternalModel) {
            abort(500, 'Model is not read-accessible and cannot be queried.');
        }

        try {
            $items = static::provider()->all()->json('data') ?? [];
        } catch (\Exception) {
            $items = [];
        }

        return collect($items)->map(function (array $attrs) {
            $model = (new static($attrs))->syncOriginal();
            static::fireModelEvent('retrieved', $model);

            return $model;
        });
    }

    public static function create(array $attributes): static
    {
        if (! static::provider() instanceof WritableExternalModel) {
            abort(500, 'Model is not write-accessible and cannot be created.');
        }

        $instance = new static($attributes);
        static::fireModelEvent('creating', $instance);

        try {
            $attrs = static::provider()->create($instance->attributes)->json('data') ?? [];
            $instance->fill($attrs ?: $instance->attributes)->syncOriginal();
        } catch (\Exception $e) {
            abort(500, 'Failed to create model.', ['error' => $e->getMessage()]);
        }

        static::fireModelEvent('created', $instance);

        return $instance;
    }

    // --- Instance API ---

    public function refresh(): void
    {
        if (! static::provider() instanceof ReadableExternalModel) {
            abort(500, 'Model is not read-accessible and cannot be queried.');
        }

        $this->fill(static::provider()->find($this->getKey())->json('data') ?? [])->syncOriginal();
        static::fireModelEvent('retrieved', $this);
    }

    public function update(array $attributes): void
    {
        if (! static::provider() instanceof WritableExternalModel) {
            abort(500, 'Model is not write-accessible and cannot be updated.');
        }

        $this->fill(array_merge($this->attributes, $attributes));
        static::fireModelEvent('updating', $this);

        try {
            $attrs = static::provider()->update($this->getKey(), $attributes)->json('data') ?? [];
            $this->fill($attrs ?: $this->attributes)->syncOriginal();
        } catch (\Exception $e) {
            abort(500, 'Failed to update model.', ['error' => $e->getMessage()]);
        }

        static::fireModelEvent('updated', $this);
    }

    public function delete(): void
    {
        if (! static::provider() instanceof RemoveableExternalModel) {
            abort(500, 'Model is not allowed to be deleted.');
        }

        static::fireModelEvent('deleting', $this);

        try {
            static::provider()->delete($this->getKey());
        } catch (\Exception $e) {
            abort(500, 'Failed to delete model.', ['error' => $e->getMessage()]);
        }

        static::fireModelEvent('deleted', $this);
    }

    public function fill(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function syncOriginal(): static
    {
        $this->original = $this->attributes;

        return $this;
    }

    // --- Dirty tracking ---

    public function isDirty(string|array|null $keys = null): bool
    {
        $dirty = $this->getDirty();

        if (is_null($keys)) {
            return count($dirty) > 0;
        }

        foreach ((array) $keys as $key) {
            if (array_key_exists($key, $dirty)) {
                return true;
            }
        }

        return false;
    }

    public function isClean(string|array|null $keys = null): bool
    {
        return ! $this->isDirty($keys);
    }

    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (! array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    public function getChanges(): array
    {
        return $this->getDirty();
    }

    public function getOriginal(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->original;
        }

        return $this->original[$key] ?? $default;
    }

    // --- Attribute access ---

    public function getAttribute(string $key): mixed
    {
        $method = 'get' . Str::studly($key) . 'Attribute';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if (! array_key_exists($key, $this->attributes)) {
            return null;
        }

        $type = $this->getCastType($key);

        return $type !== null
            ? $this->castAttribute($this->attributes[$key], $type)
            : $this->attributes[$key];
    }

    protected function getKey(): string|int
    {
        return $this->attributes[$this->identifier] ?? 0;
    }

    private function getCastType(string $key): ?string
    {
        $casts = $this->timestamps
            ? array_merge(['created_at' => 'datetime', 'updated_at' => 'datetime'], $this->casts)
            : $this->casts;

        return $casts[$key] ?? null;
    }

    private function castAttribute(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'string' => (string) $value,
            'array' => (array) $value,
            'json' => json_decode($value, true),
            'datetime' => Carbon::parse($value),
            'date' => Carbon::parse($value)->startOfDay(),
            default => enum_exists($type) ? $type::from($value) : $value,
        };
    }

    // --- Serialization ---

    public function toArray(): array
    {
        $attributes = $this->attributes;

        foreach ($this->appends as $key) {
            $attributes[$key] = $this->getAttribute($key);
        }

        return $attributes;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    // --- Magic methods ---

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}
