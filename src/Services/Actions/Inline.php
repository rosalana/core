<?php

namespace Rosalana\Core\Services\Actions;

use Illuminate\Broadcasting\Channel;
use Laravel\SerializableClosure\SerializableClosure;
use Rosalana\Core\Contracts\Action;

class Inline implements Action
{
    protected bool $isQueueable = false;
    protected bool $isBroadcastable = false;

    protected array $broadcastOn = [];
    protected ?string $broadcastAs = null;

    protected SerializableClosure $handler;

    public function __construct(
         \Closure $handler,
    ) {
        $this->handler = new SerializableClosure($handler);
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }

    public function handler()
    {
        return $this->handler->getClosure();
    }

    public function handle(): void
    {
        ($this->handler())();
    }

    public function queue(): static
    {
        $this->isQueueable = true;
        return $this;
    }

    public function isQueueable(): bool
    {
        return $this->isQueueable;
    }

    public function broadcast(array|string|null $channels = null, ?string $as = null): static
    {
        $this->isBroadcastable = true;

        if ($channels !== null) {
            $this->broadcastOn = is_array($channels) ? $channels : [$channels];
        }

        if ($as !== null) {
            $this->broadcastAs = $as;
        }

        return $this;
    }

    public function broadcastOn(): array
    {
        return array_map(
            fn($channel) => new Channel($channel),
            $this->broadcastOn
        );
    }

    public function broadcastAs(): ?string
    {
        return $this->broadcastAs;
    }

    public function isBroadcastable(): bool
    {
        return $this->isBroadcastable;
    }
}
