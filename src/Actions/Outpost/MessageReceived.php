<?php

namespace Rosalana\Core\Actions\Outpost;

use Rosalana\Core\Services\Outpost\Listener;
use Rosalana\Core\Services\Outpost\Message;

class MessageReceived
{
    protected Message $message;

    public function __construct(string $id, array $payload)
    {
        $this->message = Message::make($id, $payload);
    }

    public function handle(): void
    {
        try {
            if ($this->handleViaRegistry()) return;
            if ($this->handleViaListener()) return;
        } catch (\Throwable $e) {
            $this->message->fail();
            return;
        }
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
