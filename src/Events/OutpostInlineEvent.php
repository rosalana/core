<?php

namespace Rosalana\Core\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Rosalana\Core\Services\Outpost\Message;

class OutpostInlineEvent implements ShouldQueue, ShouldBroadcast
{
    use Dispatchable;

    public function __construct(
        protected \Closure $handle,
        protected Message $message,
        protected bool $broadcast = false,
        protected bool $queue = false,
        // ... broadcastOn, broadcastAs etc. ...
    ) {}

    public function handle(): void
    {
        ($this->handle)($this->message);
    }

    public function broadcastOn()
    {
        // Implement your broadcast channels here
    }

    public function broadcastWhen(): bool
    {
        return $this->broadcast;
    }

    public function shouldQueue(): bool
    {
        return $this->queue;
    }

}
