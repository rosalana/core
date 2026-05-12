<?php

namespace Rosalana\Core\Traits\ExternalModel;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

trait HasAttributes
{
    protected array $attributes = [];

    protected array $original = [];

    protected array $casts = [];

    protected array $appends = [];

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
}
