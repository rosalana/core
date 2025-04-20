<?php

namespace Rosalana\Core\Services\Pipeline;

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
    public function extend(callable $pipe): static
    {
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
