<?php

namespace Rosalana\Core\Services\Actions;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;

class Action implements ShouldBroadcast, ShouldQueue
{
    protected bool $shouldBroadcast = false;
    protected bool $shouldQueue = false;

    protected array $broadcastOn = [];
    protected ?string $broadcastAs = null;

    public function __construct(
        protected \Closure $handler,
    ) {}

    public static function make(...$args): static
    {
        return new static(...$args);
    }

    public function handle(): void
    {
        ($this->handler)();
    }

    public function queue(): static
    {
        $this->shouldQueue = true;
        return $this;
    }

    public function shouldQueue(): bool
    {
        return $this->shouldQueue;
    }

    public function broadcast(array|string|null $channels = null, ?string $as = null): static
    {
        $this->shouldBroadcast = true;

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

    public function broadcastWhen(): bool
    {
        return $this->shouldBroadcast;
    }
}
