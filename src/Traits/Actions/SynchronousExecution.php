<?php

namespace Rosalana\Core\Traits\Actions;

trait SynchronousExecution
{
    public function isQueueable(): bool
    {
        return false;
    }

    public function isBroadcastable(): bool
    {
        return false;
    }
}