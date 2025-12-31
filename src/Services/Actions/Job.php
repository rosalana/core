<?php

namespace Rosalana\Core\Services\Actions;

final class Job
{
    public function __construct(public object $action) {}

    public function handle(): void
    {
        if (method_exists($this->action, 'handle')) {
            $this->action->handle();
        }
    }
}
