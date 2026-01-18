<?php

namespace Rosalana\Core\Actions\Outpost;

use Rosalana\Core\Contracts\Action;
use Rosalana\Core\Facades\App;
use Rosalana\Core\Facades\Outpost;
use Rosalana\Core\Facades\Trace;
use Rosalana\Core\Services\Outpost\Listener;
use Rosalana\Core\Services\Outpost\Message;
use Rosalana\Core\Traits\Actions\SynchronousExecution;

class MessageReceived implements Action
{
    use SynchronousExecution;

    protected Message $message;

    public function __construct(string $id, array $payload)
    {
        $this->message = Trace::capture(function () use ($id, $payload) {

            $message = Message::make($id, $payload);

            Trace::decision([
                'message' => $message,
            ]);

            return $message;
        }, 'Outpost:message');
    }

    public function handle(): void
    {
        $scope = Trace::phase('Outpost:handle');

        App::hooks()->run('outpost:received', $this->message);

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
                $handler->handle($this->message);

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
        Trace::record($provider . ':check');

        try {
            return (bool) $process();
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
