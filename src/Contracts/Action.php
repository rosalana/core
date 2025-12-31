<?php

namespace Rosalana\Core\Contracts;

interface Action
{
    /**
     * Handle the action.
     */
    public function handle(): void;

    /**
     * Determine if the action should be queued.
     */
    public function isQueueable(): bool;

    /**
     * Determine if the action should be broadcasted.
     */
    public function isBroadcastable(): bool;
}