<?php

namespace Rosalana\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Rosalana\Core\Services\Outpost\Message;
use Rosalana\Core\Services\Outpost\Listener;

class OutpostMessageReceivedEvent
{
    use Dispatchable;

    protected Message $message;

    public function __construct(string $id, array $payload)
    {
        $this->message = Message::make($id, $payload);
    }

    public function handle(): void
    {
        if ($this->handleViaRegistry()) {
            return;
        }

        if ($this->handleViaListener()) {
            return;
        }

        $this->message->unreachable();
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
