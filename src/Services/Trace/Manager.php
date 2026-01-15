<?php

namespace Rosalana\Core\Services\Trace;

use Rosalana\Core\Facades\App;
use Rosalana\Core\Services\Trace\Rendering\Registry;

class Manager
{
    protected bool $enabled = true;

    public function __construct(protected Context $context)
    {
        $this->enabled = App::config('tracer.runtime.enabled', true);
    }

    /**
     * Start a new trace.
     * 
     * @param string|null $name
     * @return void
     */
    public function start(?string $name = null): void
    {
        if (! $this->enabled) return;

        $this->context->start($name);
    }

    /**
     * Start a new phase within the current trace.
     * 
     * @param string|null $name
     * @return Scope
     */
    public function phase(?string $name = null): Scope
    {
        if (! $this->enabled) return new Scope($this->context, new Trace('disabled'));

        return $this->context->phase($name);
    }

    /**
     * Finish the current trace.
     * 
     * @return Trace
     */
    public function finish(): Trace
    {
        if (! $this->enabled) {
            return new Trace('__disabled');
        }

        $trace = $this->context->finish();

        return $trace;
    }

    /**
     * Capture a process within a new trace.
     * 
     * @param \Closure $process
     * @param string|null $name
     * @return mixed
     */
    public function capture(\Closure $process, ?string $name = null): mixed
    {
        if (! $this->enabled) {
            return $process();
        }

        return $this->context->capture($process, $name);
    }

    /**
     * Record data in the current trace.
     * 
     * @param mixed $data
     * @return void
     */
    public function record(mixed $data = null): void
    {
        if (! $this->enabled) return;

        $this->context->record($data);
    }

    /**
     * Record data in the current trace when a condition is met.
     * 
     * @param bool $condition
     * @param mixed $data
     * @return void
     */
    public function recordWhen(bool $condition, mixed $data = null): void
    {
        if (! $this->enabled || ! $condition) return;

        $this->context->record($data);
    }

    /**
     * Record an exception in the current trace.
     * 
     * @param \Throwable $exception
     * @param mixed $data
     * @return void
     */
    public function exception(\Throwable $exception, mixed $data = null): void
    {
        if (! $this->enabled) return;

        $this->context->exception($exception, $data);
    }

    /**
     * Record a decision in the current trace.
     * 
     * @param mixed $data
     * @return void
     */
    public function decision(mixed $data = null): void
    {
        if (! $this->enabled) return;

        $this->context->decision($data);
    }

    /**
     * Record a decision in the current trace when a condition is met.
     * 
     * @param bool $condition
     * @param mixed $data
     * @return void
     */
    public function decisionWhen(bool $condition, mixed $data = null): void
    {
        if (! $this->enabled || ! $condition) return;

        $this->context->decision($data);
    }

    /**
     * Record either a decision or a record in the current trace.
     * 
     * @param bool $isDecision
     * @param mixed $data
     * @return void
     */
    public function recordOrDecision(bool $isDecision, mixed $data = null): void
    {
        if (! $this->enabled) return;

        if ($isDecision) {
            $this->context->decision($data);
        } else {
            $this->context->record($data);
        }
    }

    /**
     * Get all recorded traces.
     * 
     * @return array<Trace>
     */
    public function getTraces(): array
    {
        if (! $this->enabled) return [];

        return $this->context->getTraces();
    }

    /**
     * Flush all recorded traces.
     * 
     * @return void
     */
    public function flush(): void
    {
        if (! $this->enabled) return;

        $this->context->flush();
    }

    /**
     * Check if tracing is enabled.
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Register a log scheme per target.
     * 
     * @param array<string, array<class-string<\Rosalana\Core\Services\Trace\Rendering\Target>>> $schemes
     * @return void
     */
    public function register(array $schemes): void
    {
        Registry::register($schemes);
    }

    /**
     * Register a target alias.
     * 
     * @param string $alias
     * @param class-string<\Rosalana\Core\Services\Trace\Rendering\Target> $class
     * @return void
     */
    public function targetAlias(string $alias, string $class): void
    {
        Registry::targetAlias($alias, $class);
    }
}
