<?php

namespace Rosalana\Core\Services\App;

use Rosalana\Core\Services\Package;
use Rosalana\Core\Session\TokenSession;

class Meta
{
    public function id(): string
    {
        return config('rosalana.basecamp.id', 'app-id');
    }

    public function secret(): string
    {
        return config('rosalana.basecamp.secret', 'app-secret');
    }

    public function key(): string
    {
        return TokenSession::get();
    }

    public function slug(): string
    {
        return config('rosalana.basecamp.name', 'rosalana-app');
    }

    public function name(): string
    {
        return ucfirst(\Illuminate\Support\Str::camel($this->slug()));
    }

    public function version(): string
    {
        return Package::version();
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return config("rosalana.$key", $default);
    }

    public function meta(): array
    {
        return [
            'author' => 'Rosalana',
        ];
    }
}
