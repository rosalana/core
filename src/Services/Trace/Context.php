<?php

namespace Rosalana\Core\Services\Trace;

class Context
{
    /** @var Trace[] */
    protected array $roots = [];

    /**
     * Stack of opened traces (actual Trace objects).
     *
     * @var Trace[]
     */
    protected array $stack = [];

    public function start(?string $name = null): void
    {
        $parent = $this->currentTrace();

        $trace = new Trace($name, $parent);
        $trace->start();

        if ($parent) {
            $parent->addPhase($trace);
        } else {
            $this->roots[] = $trace;
        }

        $this->stack[] = $trace;
    }

    public function phase(?string $name = null): Scope
    {
        $this->start($name);
        return new Scope($this, $this->currentTrace());
    }

    public function finish(): ?Trace
    {
        while ($this->currentTrace() !== null) {
            $top = $this->currentTrace();
            if (! $top) break;

            $this->endTrace($top);
        }

        return $this->roots[0] ?? null;
    }

    public function capture(\Closure $process, ?string $name = null): mixed
    {
        $scope = $this->phase($name);

        try {
            return $process();
        } catch (\Throwable $e) {
            $this->exception($e, ['class' => static::class]);
            throw $e;
        } finally {
            $scope->close();
        }
    }

    public function record(mixed $data = null): void
    {
        if ($trace = $this->currentTrace()) {
            $trace->record($data);
        }
    }

    public function exception(\Throwable $exception, mixed $data = null): void
    {
        if ($trace = $this->currentTrace()) {
            $trace->fail($exception, $data);
        }
    }

    public function decision(mixed $data = null): void
    {
        if ($trace = $this->currentTrace()) {
            $trace->decision($data);
        }
    }

    /**
     * @return Trace[]
     */
    public function getTraces(): array
    {
        return $this->roots;
    }

    public function flush(): void
    {
        $this->roots = [];
        $this->stack = [];
    }

    public function endTrace(Trace $trace): void
    {
        $id = $trace->id();

        $index = $this->findInStack($trace);

        if ($index === null) {
            $trace->finish();
            return;
        }

        while (! empty($this->stack)) {
            $top = $this->currentTrace();
            if ($top === null) break;

            $top->finish();
            $this->popTrace();

            if ($top->id() === $id) {
                break;
            }
        }
    }

    public function currentTrace(): ?Trace
    {
        $trace = end($this->stack);
        return $trace === false ? null : $trace;
    }

    protected function popTrace(): ?Trace
    {
        $popped = array_pop($this->stack);
        return $popped ?: null;
    }

    protected function findInStack(Trace $trace): ?int
    {
        $id = $trace->id();

        foreach ($this->stack as $i => $item) {
            if ($item->id() === $id) {
                return $i;
            }
        }

        return null;
    }
}
