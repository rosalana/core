<?php

namespace Rosalana\Core\Services\Actions;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Rosalana\Core\Contracts\Action;

final class Queue implements ShouldQueue
{
    use SerializesModels;

    public function __construct(protected Action $action) {}

    public function handle(): void
    {
        if (method_exists($this->action, 'handle')) {
            $this->action->handle();
        }

        if ($this->action->isBroadcastable()) {
            broadcast(new Broadcast($this->action));
        }
    }
}
