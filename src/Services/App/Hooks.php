<?php

namespace Rosalana\Core\Services\App;

use Rosalana\Core\Facades\Pipeline;
use Illuminate\Support\Str;

class Hooks
{
    // dont need to use - you can just call run()
    public function register(string $alias)
    {
        $this->validateAlias($alias);
        Pipeline::register('app:hooks:' . $alias);
    }

    public function on(string $alias, callable $callback)
    {
        $this->validateAlias($alias);
        Pipeline::extend('app:hooks:' . $alias, function ($data, $next) use ($callback) {
            $callback($data);
            return $next($data);
        });
    }

    public function run(string $alias, mixed $payload = null)
    {
        $this->validateAlias($alias);
        return Pipeline::resolve('app:hooks:' . $alias)
            ->run($payload);
    }

    public function exists(string $alias): bool
    {
        $this->validateAlias($alias);
        return Pipeline::exists('app:hooks:' . $alias);
    }

    public function __call(string $method, array $arg)
    {
        if (!Str::startsWith($method, 'on')) {
            throw new \BadMethodCallException("Method [$method] does not exist.");
        }

        /** Works for: onGroupAction */
        $raw = substr($method, 2);
        $alias = str_replace(['_', '.'], ':', Str::lower(Str::snake($raw)));

        $this->validateAlias($alias);

        return $this->on($alias, ...$arg);
    }


    protected function validateAlias(string $alias): void
    {
        if (!Str::contains($alias, ':') || count(explode(':', $alias)) !== 2) {
            throw new \InvalidArgumentException("Alias '$alias' is not valid. It should be in the format 'group:name'.");
        }
    }
}
