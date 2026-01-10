<?php

namespace Rosalana\Core\Services\Outpost;

use Rosalana\Core\Contracts\Action;
use Rosalana\Core\Facades\Trace;

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
        Trace::capture(function () use ($message) {

            Trace::record([
                'silent' => $this->isSilent(),
            ]);

            $result = ($this->callback)($message);

            if ($result instanceof Action) {
                run($result);
            }

            Trace::decision([
                'handler' => 'Resolved from Outpost Registry',
                'queued' => $result instanceof Action ? $result->isQueueable() : false,
                'broadcasted' => $result instanceof Action ? $result->isBroadcastable() : false,
                'silent' => $this->isSilent(),
            ]);
        }, 'Outpost:handler:registry:listener');
    }
}
