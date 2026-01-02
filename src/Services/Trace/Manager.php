<?php

namespace Rosalana\Core\Services\Trace;

use Rosalana\Core\Facades\App;

class Manager
{
    protected bool $enabled = true;

    public function __construct(protected Context $context)
    {
        $this->enabled = App::config('tracer.enabled', true);
    }

    public function start(?string $name = null): void
    {
        if (! $this->enabled) return;

        $this->context->start($name);
    }

    public function phase(?string $name = null): Scope
    {
        if (! $this->enabled) return new Scope($this->context, new Trace('disabled'));

        return $this->context->phase($name);
    }

    public function finish(): ?Trace
    {
        if (! $this->enabled) return null;

        return $this->context->finish();
    }

    public function capture(\Closure $process, ?string $name = null): mixed
    {
        if (! $this->enabled) {
            return $process();
        }

        return $this->context->capture($process, $name);
    }

    public function record(mixed $data = null): void
    {
        if (! $this->enabled) return;

        $this->context->record($data);
    }

    public function exception(\Throwable $exception, mixed $data = null): void
    {
        if (! $this->enabled) return;

        $this->context->exception($exception, $data);
    }

    public function fail(\Throwable $exception, mixed $data = null): void
    {
        $this->exception($exception, $data);
    }

    public function getTraces(): array
    {
        if (! $this->enabled) return [];

        return $this->context->getTraces();
    }

    public function flush(): void
    {
        if (! $this->enabled) return;

        $this->context->flush();
    }
}
