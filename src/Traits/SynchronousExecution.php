<?php

namespace Rosalana\Core\Traits;

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