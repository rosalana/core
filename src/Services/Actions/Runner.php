<?php

namespace Rosalana\Core\Services\Actions;

use Rosalana\Core\Contracts\Action;

class Runner
{
    public static function run(Action $action): void
    {
        if ($action->isQueueable()) {

            if ($action instanceof Inline) {
                dispatch_sync(new Queue($action)); // for now
            } else {
                dispatch(new Queue($action));
            }

            return;
        }

        $action->handle();

        if ($action->isBroadcastable()) {
            broadcast(new Broadcast($action));
        }
    }
}
