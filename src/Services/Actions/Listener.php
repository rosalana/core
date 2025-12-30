<?php

namespace Rosalana\Core\Services\Actions;

use Illuminate\Contracts\Queue\ShouldQueue;

final class Listener implements ShouldQueue
{
    public function handle(Event $event): void
    {
        if (method_exists($event->action, 'handle')) {
            $event->action->handle();
        }
    }

    public function shouldQueue(Event $event): bool
    {
        if (! $event->action instanceof ShouldQueue) {
            return false;
        }

        if (method_exists($event->action, 'shouldQueue')) {
            return (bool) $event->action->shouldQueue();
        }

        return true;
    }
}
