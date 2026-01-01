<?php

namespace Rosalana\Core\Services\Outpost;

use Rosalana\Core\Contracts\Action;

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
        $result = ($this->callback)($message);

        if ($result instanceof Action) {
            run($result);
        }
    }
}
