<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rosalana\Core\Actions\Outpost\Inline;
use Rosalana\Core\Exceptions\Service\Outpost\InvalidMessageNamespaceException;
use Rosalana\Core\Facades\App;
use Rosalana\Core\Facades\Outpost;

class Message
{
    public const array ALLOWED_STATUSES = [
        'request',
        'confirmed',
        'failed',
        'unreachable',
    ];

    public function __construct(
        public readonly string $id,
        public readonly string $namespace,
        public readonly array $payload,
        public readonly string $from,
        public readonly ?string $correlationId = null,
        public readonly ?int $timestamp = null,
    ) {}

    public static function make(string $id, array $data): static
    {
        $i = new static(
            id: $id,
            namespace: $data['namespace'] ?? '',
            payload: $data['payload'] ? json_decode($data['payload'], true) : [],
            from: $data['from'] ?? '',
            correlationId: $data['correlation_id'] ?? null,
            timestamp: isset($data['timestamp']) ? (int)$data['timestamp'] : null,
        );

        $i->ensureValid();

        return $i;
    }

    public function payload(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->payload, $key, $default);
    }

    public function status(): string
    {
        return explode(':', $this->namespace)[1] ?? 'unknown';
    }

    public function name(): string
    {
        return explode(':', $this->namespace)[0] ?? 'unknown';
    }

    public function isFrom(string $source): bool
    {
        return $source === $this->from;
    }

    public function promise(): Promise|null
    {
        return Promise::get($this);
    }

    public function listenersClass(): string
    {
        $prefix = App::config('outpost.namespace_prefix', 'App\\Outpost\\');
        $class = implode('\\', array_map(fn($part) => Str::studly($part), explode('.', $this->name())));

        return $prefix . $class;
    }

    public function event(\Closure $handle): Inline
    {
        return Inline::make($handle, $this);
    }

    public function response()
    {
        return Outpost::responseTo($this);
    }

    public function confirm(array $payload = []): void
    {
        Outpost::responseTo($this)->confirm(payload: $payload);
    }

    public function fail(array $payload = []): void
    {
        Outpost::responseTo($this)->fail(payload: $payload);
    }

    public function unreachable(array $payload = []): void
    {
        Outpost::responseTo($this)->unreachable(payload: $payload);
    }

    protected function ensureValid(): void
    {
        if (! is_string($this->namespace) || ! preg_match('/^[a-z]+\\.[a-z]+:[a-z]+$/', $this->namespace) || ! in_array(explode(':', $this->namespace, 2)[1], static::ALLOWED_STATUSES)) {
            throw new InvalidMessageNamespaceException($this);
        }
    }
}
