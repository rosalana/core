<?php

namespace Rosalana\Core\Services\Actions;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Rosalana\Core\Contracts\Action;

final class Broadcast implements ShouldBroadcast
{
    public function __construct(protected Action $action) {}

    public function broadcastOn()
    {
        if (method_exists($this->action, 'broadcastOn')) {
            return (array) $this->action->broadcastOn();
        }

        return [];
    }

    public function broadcastAs()
    {
        if (method_exists($this->action, 'broadcastAs')) {
            return $this->action->broadcastAs();
        }

        return null;
    }
}
