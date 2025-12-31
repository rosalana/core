<?php

namespace Rosalana\Core\Services\Outpost;

use Laravel\SerializableClosure\SerializableClosure;
use Rosalana\Core\Facades\App;

class Promise
{
    public function __construct(protected Message $message) {}

    public static function get(Message $message): self|null
    {
        $i = new self($message);

        if ($i->hasCallback()) {
            return $i;
        }

        return null;
    }

    public function resolve(): void
    {
        call_user_func($this->retrieveCallback(), $this->message);

        $this->clearCallbacks();
    }

    public function reject(): void
    {
        $this->clearCallbacks();
    }

    public function onConfirm(callable $callback): self
    {
        $this->storeCallback('confirmed', $callback);
        return $this;
    }

    public function onFail(callable $callback): self
    {
        $this->storeCallback('failed', $callback);
        return $this;
    }

    public function onUnreachable(callable $callback): self
    {
        $this->storeCallback('unreachable', $callback);
        return $this;
    }

    protected function storeCallback(string $status, callable $callback): void
    {
        if ($callback instanceof \Closure) {
            $callback = new SerializableClosure($callback);
        }

        App::context()->put($this->key($status), $callback);
    }

    protected function retrieveCallback(): callable|null
    {
        $cb = App::context()->get($this->key($this->message->status()));

        if ($cb instanceof SerializableClosure) {
            return $cb->getClosure();
        }

        return is_callable($cb) ? $cb : null;
    }

    protected function clearCallbacks(): void
    {
        App::context()->forget($this->key());
    }

    protected function hasCallback(): bool
    {
        return App::context()->has($this->key($this->message->status()));
    }

    protected function key(?string $status = null): string
    {
        if (is_null($status)) {
            return "promise.{$this->message->correlationId}";
        }

        return "promise.{$this->message->correlationId}.{$status}";
    }
}
