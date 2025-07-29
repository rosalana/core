<?php

namespace Rosalana\Core\Services\App;

use Illuminate\Http\Client\Response;
use Rosalana\Core\Facades\Basecamp;

class Manager
{
    protected Meta $meta;
    protected Hooks $hooks;

    public function __construct(Meta $meta, Hooks $hooks)
    {
        $this->meta = $meta;
        $this->hooks = $hooks;
    }

    public function id(): string
    {
        return $this->meta->id();
    }

    public function key(): string
    {
        return $this->meta->key();
    }

    public function slug(): string
    {
        return $this->meta->slug();
    }

    public function name(): string
    {
        return $this->meta->name();
    }

    public function version(): string
    {
        return $this->meta->version();
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return $this->meta->config($key, $default);
    }

    public function meta(): array
    {
        return $this->meta->meta();
    }

    public function self(): Response
    {
        return Basecamp::apps()->find($this->slug());
    }

    public function list(): Response
    {
        return Basecamp::apps()->all();
    }

    public function find(string $name): Response
    {
        return Basecamp::apps()->find($name);
    }

    public function context(): Context
    {
        return app('rosalana.context');
    }

    public function hooks(): Hooks
    {
        return $this->hooks;
    }
}