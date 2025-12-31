<?php

namespace Rosalana\Core\Actions\Outpost;

use Rosalana\Core\Contracts\Action;
use Rosalana\Core\Services\Outpost\Listener;
use Rosalana\Core\Services\Outpost\Message;
use Rosalana\Core\Traits\Actions\SynchronousExecution;

class MessageReceived implements Action
{
    use SynchronousExecution;

    protected Message $message;

    public function __construct(string $id, array $payload)
    {
        $this->message = Message::make($id, $payload);
    }

    public function handle(): void
    {
        try {
            if ($this->handleViaCorrelation()) return;
            if ($this->handleViaRegistry()) return;
            if ($this->handleViaListener()) return;
        } catch (\Throwable $e) {
            $this->message->fail(['error' => $e->getMessage()]);
            return;
        }
    }

    protected function handleViaCorrelation(): bool
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
        return false; // Registry z Service Provideru (listen())
    }

    protected function handleViaListener(): bool
    {
        if (class_exists($this->message->listenersClass())) {
            $handler = new ($this->message->listenersClass())();

            if (method_exists($handler, 'handle') && $handler instanceof Listener) {
                $handler->handle($this->message);
                return true;
            }
        }

        return false;
    }
}
