<?php

namespace Rosalana\Core\Services\Trace;

class Manager
{
    public function __construct(protected Context $context) {}

    public function start(?string $name = null): void
    {
        $this->context->start($name);
    }

    public function phase(?string $name = null): Scope
    {
        return $this->context->phase($name);
    }

    public function finish(): ?Trace
    {
        return $this->context->finish();
    }

    public function wrap(\Closure $process, ?string $name = null): mixed
    {
        return $this->context->wrap($process, $name);
    }

    public function record(mixed $data = null): void
    {
        $this->context->record($data);
    }

    public function exception(\Throwable $exception, mixed $data = null): void
    {
        $this->context->exception($exception, $data);
    }

    public function fail(\Throwable $exception, mixed $data = null): void
    {
        $this->exception($exception, $data);
    }

    public function getTraces(): array
    {
        return $this->context->getTraces();
    }

    public function flush(): void
    {
        $this->context->flush();
    }
}
