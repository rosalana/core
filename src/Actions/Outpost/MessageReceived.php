<?php

namespace Rosalana\Core\Actions\Outpost;

use Rosalana\Core\Contracts\Action;
use Rosalana\Core\Facades\Outpost;
use Rosalana\Core\Services\Outpost\Listener;
use Rosalana\Core\Services\Outpost\Message;
use Rosalana\Core\Traits\Actions\SynchronousExecution;

class MessageReceived implements Action
{
    use SynchronousExecution;

    protected Message $message;

    public string $executedVia = 'none';
    public bool $executedToQueue = false;
    public bool $executedToBroadcast = false;

    public function __construct(string $id, array $payload)
    {
        $this->message = Message::make($id, $payload);
    }

    public function handle(): void
    {
        try {
            if ($this->checkProvider('promise', fn() => $this->handleViaPromise())) return;
            if ($this->checkProvider('registry', fn() => $this->handleViaRegistry())) return;
            if ($this->checkProvider('listener', fn() => $this->handleViaListener())) return;
        } catch (\Throwable $e) {
            $this->message->fail(['error' => $e->getMessage()]);
            return;
        }

        $this->message->unreachable();
    }

    protected function handleViaPromise(): bool
    {
        $promise = $this->message->promise();

        if ($promise) {
            $promise->resolve();
            return true;
        }

        return false;
    }

    protected function handleViaRegistry(): bool
    {
        return Outpost::runRegistry($this->message);
    }

    protected function handleViaListener(): bool
    {
        if (class_exists($this->message->listenersClass())) {
            $handler = new ($this->message->listenersClass())();

            if (method_exists($handler, 'handle') && $handler instanceof Listener) {
                $action = $handler->handle($this->message);

                if ($action instanceof Action) {
                    $this->executedToQueue = $action->isQueueable();
                    $this->executedToBroadcast = $action->isBroadcastable();
                }

                return true;
            }
        }

        return false;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    protected function checkProvider(string $provider, \Closure $process): bool
    {
        try {
            $result = (bool) $process();

            if ($result) {
                $this->executedVia = $provider;
            }

            return $result;
        } catch (\Throwable $e) {
            $this->executedVia = 'failed:' . $provider;
            throw $e;
        }
    }
}
