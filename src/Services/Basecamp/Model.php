<?php

namespace Rosalana\Core\Services\Basecamp;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rosalana\Core\Contracts\Basecamp\Model\ReadableExternalModel;
use Rosalana\Core\Contracts\Basecamp\Model\RemoveableExternalModel;
use Rosalana\Core\Contracts\Basecamp\Model\WritableExternalModel;
use Rosalana\Core\Traits\ExternalModel\HasAttributes;
use Rosalana\Core\Traits\ExternalModel\HasEvents;

abstract class Model
{
    use HasEvents, HasAttributes;

    /** @var array<string, bool> */
    protected static $booted = [];

    /** @var string */
    protected $identifier = 'id';

    /** @var bool */
    protected $timestamps = true;

    public function __construct(array $attributes = [])
    {
        static::bootIfNotBooted();
        $this->fill($attributes);
    }

    protected static function provider(): object
    {
        return app('rosalana.basecamp')->{Str::plural(Str::camel(class_basename(static::class)))}();
    }

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

    protected function getKey(): string|int
    {
        return $this->attributes[$this->identifier] ?? 0;
    }

    public function toArray(): array
    {
        $attributes = $this->attributes;

        foreach ($this->appends as $key) {
            $attributes[$key] = $this->getAttribute($key);
        }

        foreach ($this->computed as $key) {
            $attributes[$key] = $this->getAttribute($key);
        }

        return $attributes;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

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
