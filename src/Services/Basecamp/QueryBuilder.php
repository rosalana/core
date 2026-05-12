<?php

namespace Rosalana\Core\Services\Basecamp;

use Illuminate\Support\Collection;
use Rosalana\Core\Contracts\Basecamp\Model\ReadableExternalModel;
use Rosalana\Core\Contracts\Basecamp\Model\WritableExternalModel;

class QueryBuilder
{
    protected array $with = [];

    public function __construct(protected object $provider, protected string $modelClass) {}

    public function with(string|array $with): static
    {
        $this->with = array_merge($this->with, (array) $with);

        return $this;
    }

    protected function newModel(array $attributes): Model
    {
        return new ($this->modelClass)($attributes, $this->with ?: null);
    }

    public function find(string|int $id): ?Model
    {
        if (! $this->provider instanceof ReadableExternalModel) {
            abort(500, 'Model is not read-accessible and cannot be queried.');
        }

        try {
            return $this->findOrFail($id);
        } catch (\Exception) {
            return null;
        }
    }

    /** @throws \Exception */
    public function findOrFail(string|int $id): Model
    {
        if (! $this->provider instanceof ReadableExternalModel) {
            abort(500, 'Model is not read-accessible and cannot be queried.');
        }

        try {
            $model = $this->newModel($this->provider->find($id)->json('data') ?? [])->syncOriginal();
        } catch (\Exception $e) {
            abort(404, "Model not found for identifier `{$id}`.", ['error' => $e->getMessage()]);
        }

        ($this->modelClass)::fireModelEvent('retrieved', $model);

        return $model;
    }

    public function all(): Collection
    {
        if (! $this->provider instanceof ReadableExternalModel) {
            abort(500, 'Model is not read-accessible and cannot be queried.');
        }

        try {
            $items = $this->provider->all()->json('data') ?? [];
        } catch (\Exception) {
            $items = [];
        }

        return collect($items)->map(function (array $attrs) {
            $model = $this->newModel($attrs)->syncOriginal();
            ($this->modelClass)::fireModelEvent('retrieved', $model);

            return $model;
        });
    }

    public function create(array $attributes): Model
    {
        if (! $this->provider instanceof WritableExternalModel) {
            abort(500, 'Model is not write-accessible and cannot be created.');
        }

        $instance = $this->newModel($attributes);
        ($this->modelClass)::fireModelEvent('creating', $instance);

        try {
            $attrs = $this->provider->create($attributes)->json('data') ?? [];
            $instance->fill($attrs ?: $attributes)->syncOriginal();
        } catch (\Exception $e) {
            abort(500, 'Failed to create model.', ['error' => $e->getMessage()]);
        }

        ($this->modelClass)::fireModelEvent('created', $instance);

        return $instance;
    }
}
