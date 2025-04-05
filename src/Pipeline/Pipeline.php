<?php

namespace Rosalana\Core\Pipeline;

use Illuminate\Pipeline\Pipeline as LaravelPipeline;

class Pipeline
{
    protected array $pipes = [];

    public function __construct(
        protected ?string $alias = null,
    ) {}

    /**
     * @param callable|string $pipe (arg. ?$payload, ?$next)
     * Argument $pipe can return void only when no arguments are passed.
     */
    public function extend(callable|string $pipe): static
    {
        if (is_callable($pipe)) {
            $reflection = new \ReflectionFunction($pipe);
            $paramCount = $reflection->getNumberOfParameters();
    
            if ($paramCount === 1) {
                $pipe = fn($payload, $next) => $next($pipe($payload));
            }
    
            if ($paramCount < 1) {
                $original = $pipe;
                $pipe = function ($payload, $next) use ($original) {
                    $original();
                    return $next($payload);
                };
            }
        }

        $this->pipes[] = $pipe;
        return $this;
    }

    /**
     * Get the pipes registered in the pipeline.
     */
    public function pipes(): array
    {
        return $this->pipes;
    }

    /**
     * Run the pipeline with the given payload.
     * @param mixed $payload
     */
    public function run(mixed $payload): mixed
    {
        return app(LaravelPipeline::class)
            ->send($payload)
            ->through($this->pipes())
            ->thenReturn();
    }
}
