<?php

namespace Rosalana\Core\Traits\Actions;

trait QueuedOnly
{
    public function isQueueable(): bool
    {
        return true;
    }

    public function isBroadcastable(): bool
    {
        return false;
    }
}