<?php

namespace Rosalana\Core\Services\Outpost;

class RegistryListener
{
    protected bool $silent = false;

    public function __construct(protected \Closure $callback) {}

    public function setSilent(bool $silent = true): void
    {
        $this->silent = $silent;
    }

    public function isSilent(): bool
    {
        return $this->silent;
    }

    public function handle(Message $message): void
    {
        ($this->callback)($message);
    }
}
