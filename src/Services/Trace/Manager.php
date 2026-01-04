<?php

namespace Rosalana\Core\Services\Trace;

use Rosalana\Core\Facades\App;
use Rosalana\Core\Services\Logging\LogRegistry;

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
     * @return Trace|null
     */
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
     * Register log schemes.
     * 
     * @param string $pattern may include wildcards (*) and variants ({opt1|opt2})
     * @param class-string<\Rosalana\Core\Services\Logging\LogScheme> $class
     * @return void
     */
    public function registerScheme(string $pattern, string $class): void
    {
        LogRegistry::registerScheme($pattern, $class);
    }

    /**
     * Register Log Schemes.
     * 
     * @param array<string, class-string<\Rosalana\Core\Services\Logging\LogScheme>> $schemes
     * @return void
     */
    public function registerSchemes(array $schemes): void
    {
        LogRegistry::registerSchemes($schemes);
    }

    /**
     * Register a log renderer.
     * 
     * @param string $name
     * @param class-string<LogRenderer> $class
     * @return void
     */
    public function registerRenderer(string $name, string $class): void
    {
        LogRegistry::registerRenderer($name, $class);
    }

    /**
     * Register Log Renderers.
     * 
     * @param array<string, class-string<\Rosalana\Core\Services\Logging\LogRenderer>> $renderers
     * @return void
     */
    public function registerRenderers(array $renderers): void
    {
        LogRegistry::registerRenderers($renderers);
    }
}
