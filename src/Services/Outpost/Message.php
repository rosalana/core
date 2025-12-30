<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rosalana\Core\Events\OutpostInlineEvent;
use Rosalana\Core\Facades\App;
use Rosalana\Core\Facades\Outpost;

class Message
{
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
        return new static(
            id: $id,
            namespace: $data['namespace'] ?? '',
            payload: $data['payload'] ?? [],
            from: $data['from'] ?? '',
            correlationId: $data['correlation_id'] ?? null,
            timestamp: isset($data['timestamp']) ? (int)$data['timestamp'] : null,
        );
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

    public function listenersClass(): string
    {
        $prefix = App::config('outpost.namespace_prefix', 'App\\Outpost\\');
        $class = Str::studly(str_replace('.', '\\', $this->name()));

        return $prefix . $class;
    }

    public function event(\Closure $handle): OutpostInlineEvent
    {
        return new OutpostInlineEvent($handle, $this);
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
}
