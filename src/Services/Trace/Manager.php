<?php

namespace Rosalana\Core\Services\Trace;

use Rosalana\Core\Facades\App;

class Manager
{
    protected bool $enabled = true;

    public function __construct(protected Context $context)
    {
        $this->enabled = App::config('tracer.runtime.enabled', true);
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

        $trace = $this->context->finish();

        $keys = App::config('tracer.runtime.log', []);

        if ($trace->name() && in_array($trace->name(), $keys, true)) {
            $trace->log();
        }

        return $trace;
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

    public function recordWhen(bool $condition, mixed $data = null): void
    {
        if (! $this->enabled || ! $condition) return;

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

    public function decision(mixed $data = null): void
    {
        if (! $this->enabled) return;

        $this->context->decision($data);
    }

    public function decisionWhen(bool $condition, mixed $data = null): void
    {
        if (! $this->enabled || ! $condition) return;

        $this->context->decision($data);
    }

    public function recordOrDecision(bool $isDecision, mixed $data = null): void
    {
        if (! $this->enabled) return;

        if ($isDecision) {
            $this->context->decision($data);
        } else {
            $this->context->record($data);
        }
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
