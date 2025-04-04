<?php

namespace Rosalana\Core\Pipeline;

use Illuminate\Pipeline\Pipeline as LaravelPipeline;

class Pipeline
{
    protected array $pipes = [];

    public function __construct(
        protected ?string $alias = null,
    ) {}
    
    public function extend(callable|string $pipe): static
    {
        $this->pipes[] = $pipe;
        return $this;
    }

    public function pipes(): array
    {
        return $this->pipes;
    }

    public function run(mixed $payload): mixed
    {
        return app(LaravelPipeline::class)
            ->send($payload)
            ->through($this->pipes())
            ->thenReturn();
    }
}
