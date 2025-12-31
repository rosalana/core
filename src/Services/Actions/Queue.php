<?php

namespace Rosalana\Core\Services\Actions;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Rosalana\Core\Contracts\Action;

final class Queue implements ShouldQueue
{
    use SerializesModels;

    public function handle(Action $action): void
    {
        if (method_exists($action, 'handle')) {
            $action->handle();
        }

        if ($action->isBroadcastable()) {
            broadcast(new Broadcast($action));
        }
    }
}
