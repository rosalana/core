<?php

namespace Rosalana\Core\Services\Actions;

final class Listener
{
    public function handle(Event $event): void
    {
        if (! method_exists($event->action, 'handle')) {
            return;
        }

        if ($event->action->isQueueable()) {
            dispatch(new Job($event->action));
            return;
        }

        $event->action->handle();
    }
}
