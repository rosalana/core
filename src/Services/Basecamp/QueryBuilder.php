<?php

namespace Rosalana\Core\Services\Basecamp;

use Illuminate\Http\Request;
use Rosalana\Core\Contracts\Basecamp\Model\ReadableExternalModel;
use Rosalana\Core\Services\Basecamp\Collection;
use Rosalana\Core\Contracts\Basecamp\Model\WritableExternalModel;

class QueryBuilder
{
    protected array $with = [];

    protected array $query = [];

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
            $response = $this->provider->find($id);

            $model = $this->newModel($response->json('data') ?? [])->syncOriginal();
            $model->fillRequestMeta($response->json('meta') ?? []);
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
            $response = $this->provider->all($this->query);
        } catch (\Exception) {
            $response = null;
        }

        $items = collect($response->json('data') ?? [])->map(function (array $attrs) {
            $model = $this->newModel($attrs)->syncOriginal();
            ($this->modelClass)::fireModelEvent('retrieved', $model);

            return $model;
        });

        return (new Collection($items))->withMeta($response->json('meta') ?? []);
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

    public function filter(string $key, mixed $value): static
    {
        $this->query['filter'][$key] = $value;
        return $this;
    }

    public function preset(string $preset): static
    {
        $this->query['preset'] = $preset;
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->query['limit'] = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->query['offset'] = $offset;
        return $this;
    }

    public function orderBy(string $attribute, string $direction = 'asc'): static
    {
        $this->query['sort'] = ($direction === 'desc' ? '-' : '') . $attribute;
        return $this;
    }

    public function orderByDesc(string $attribute): static
    {
        return $this->orderBy($attribute, 'desc');
    }

    public function orderByAsc(string $attribute): static
    {
        return $this->orderBy($attribute, 'asc');
    }

    public function search(string $query): static
    {
        $this->query['search'] = $query;
        return $this;
    }

    public function paginate(int $page = 1, int $perPage = 10): static
    {
        return $this->limit($perPage)->offset(($page - 1) * $perPage);
    }

    public function withRequest(Request $request): static
    {
        if ($request->has('filter')) {
            foreach ($request->input('filter') as $key => $value) {
                $this->filter($key, $value);
            }
        }

        if ($request->has('preset')) {
            $this->preset($request->input('preset'));
        }

        if ($request->has('search')) {
            $this->search($request->input('search'));
        }

        if ($request->has('sort')) {
            $sort = $request->input('sort');

            if (is_array($sort) && ! empty($sort['id'])) {
                $this->orderBy($sort['id'], $sort['order'] ?? 'asc');
            } elseif (is_string($sort) && $sort !== '') {
                str_starts_with($sort, '-')
                    ? $this->orderBy(substr($sort, 1), 'desc')
                    : $this->orderBy($sort, 'asc');
            }
        }

        if ($request->has('page') && $request->has('per_page')) {
            $this->paginate($request->input('page', 1), $request->input('per_page', 10));
        }

        return $this;
    }
}
