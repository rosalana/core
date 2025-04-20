<?php

namespace Rosalana\Core\Services\Outpost;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class Packet implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue;

    public function __construct(
        public string $alias, // e.g. 'notification.email'
        public string $origin, // this app-name!! | app-secret
        public ?string $target, // app-name!! | app-secret
        public array $payload,
    ) {}

    public function handle()
    {
        $callback = OutpostRegistry::resolve($this->alias);

        if ($callback) {
            call_user_func($callback, $this->payload);
        }
    }

    public function toArray()
    {
        return [
            'alias' => $this->alias,
            'origin' => $this->origin,
            'target' => $this->target,
            'payload' => $this->payload,
        ];
    }

    public function fromArray(array $payload): self
    {
        return new self(
            alias: $payload['alias'],
            origin: $payload['origin'],
            target: $payload['target'],
            payload: $payload['payload'],
        );
    }
}
