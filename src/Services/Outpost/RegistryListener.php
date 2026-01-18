<?php

namespace Rosalana\Core\Services\Outpost;

use Rosalana\Core\Contracts\Action;
use Rosalana\Core\Facades\Trace;

class RegistryListener
{
    protected bool $silent = false;

    public function __construct(protected string $registeredAs, protected \Closure $callback, protected string $name = 'unnamed') {}

    public function name(): string
    {
        if ($this->isWilcard()) {
            return "{$this->name}  ({$this->registeredAs})";
        }

        return $this->name;
    }

    public function setSilent(bool $silent = true): void
    {
        $this->silent = $silent;
    }

    public function isSilent(): bool
    {
        return $this->silent;
    }

    public function isWilcard(): bool
    {
        return matches($this->name)->isWildcard();
    }

    public function handle(Message $message): void
    {
        Trace::capture(function () use ($message) {

            Trace::record([
                'silent' => $this->isSilent(),
                'wildcard' => $this->isWilcard(),
            ]);

            $result = ($this->callback)($message);

            if ($result instanceof Action) {
                run($result);
            }

            Trace::decision([
                'handler' => 'Resolved `' . $this->name() . '`',
                'queued' => $result instanceof Action ? $result->isQueueable() : false,
                'broadcasted' => $result instanceof Action ? $result->isBroadcastable() : false,
                'silent' => $this->isSilent(),
                'wildcard' => $this->isWilcard(),
            ]);
        }, 'Outpost:handler:registry:listener');
    }
}
