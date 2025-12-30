<?php

namespace Rosalana\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Rosalana\Core\Services\Outpost\Message;

class OutpostInlineEvent implements ShouldQueue, ShouldBroadcast
{
    use Dispatchable;

    protected bool $shouldBroadcast = false;
    protected bool $shouldQueue = false;

    protected array $broadcastOn = [];
    protected ?string $broadcastAs = null;

    public function __construct(
        protected \Closure $handle,
        protected Message $message,
    ) {
        $this->broadcastOn = [str_replace('.', '-', $this->message->name())];
        $this->broadcastAs = $this->message->name() . '.' . $this->message->status();
    }

    public function handle(): void
    {
        ($this->handle)($this->message);
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
