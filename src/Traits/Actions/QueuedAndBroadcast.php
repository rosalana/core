<?php

namespace Rosalana\Core\Traits\Actions;

trait QueuedAndBroadcast
{
    public function isQueueable(): bool
    {
        return true;
    }

    public function isBroadcastable(): bool
    {
        return true;
    }
}