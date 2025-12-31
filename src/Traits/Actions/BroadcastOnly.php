<?php

namespace Rosalana\Core\Traits\Actions;

trait BroadcastOnly
{
    public function isQueueable(): bool
    {
        return false;
    }

    public function isBroadcastable(): bool
    {
        return true;
    }
}