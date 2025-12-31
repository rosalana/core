<?php

namespace Rosalana\Core\Services\Actions;

use Rosalana\Core\Contracts\Action;

class Runner
{
    public static function run(Action $action): Action
    {
        if ($action->isQueueable()) {
            dispatch(new Queue($action));
            return $action;
        }

        $action->handle();

        if ($action->isBroadcastable()) {
            broadcast(new Broadcast($action));
        }

        return $action;
    }
}
