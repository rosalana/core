<?php

namespace Rosalana\Core\Services\Actions;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

final class Event implements ShouldBroadcast
{
    protected bool $shouldBroadcast = false;

    public function __construct(
        public object $action
    ) {
        $this->shouldBroadcast = $action instanceof ShouldBroadcast;
    }

    public function broadcastOn()
    {
        if (! $this->shouldBroadcast) return [];

        if (method_exists($this->action, 'broadcastOn')) {
            return (array) $this->action->broadcastOn();
        }
    }

    public function broadcastAs()
    {
        if (! $this->shouldBroadcast) return null;

        if (method_exists($this->action, 'broadcastAs')) {
            return $this->action->broadcastAs();
        }
    }

    public function broadcastWhen()
    {
        if (! $this->shouldBroadcast) return false;

        if (method_exists($this->action, 'broadcastWhen')) {
            return (bool) $this->action->broadcastWhen();
        }
    }
}
