<?php

namespace Rosalana\Core\Services\Actions;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

final class Event implements ShouldBroadcast
{
    protected bool $isBroadcastable = false;

    public function __construct(
        public object $action
    ) {
        $this->isBroadcastable = $action->isBroadcastable();
    }

    public function broadcastOn()
    {
        if (! $this->isBroadcastable) return [];

        if (method_exists($this->action, 'broadcastOn')) {
            return (array) $this->action->broadcastOn();
        }

        return [];
    }

    public function broadcastAs()
    {
        if (! $this->isBroadcastable) return null;

        if (method_exists($this->action, 'broadcastAs')) {
            return $this->action->broadcastAs();
        }

        return null;
    }
}
