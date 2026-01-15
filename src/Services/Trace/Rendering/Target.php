<?php

namespace Rosalana\Core\Services\Trace\Rendering;

use Rosalana\Core\Services\Trace\Trace;

abstract class Target
{
    protected array $lines = [];

    public function __construct(protected Trace $trace) {}

    /**
     * Add a new line to the rendered output.
     * 
     * @return void
     */
    public function newLine(): void
    {
        $this->lines[] = [];
    }

    /**
     * Add a token to the current line.
     * 
     * @param string $value
     * @return void
     */
    public function token(string $value): void
    {
        $this->getCurrentLine()[] = ['value' => $value];
    }

    /**
     * Get the current line.
     * 
     * @return array
     */
    protected function &getCurrentLine(): array
    {
        if (empty($this->lines)) {
            $this->newLine();
        }

        return $this->lines[array_key_last($this->lines)];
    }

    /**
     * Build the rendering.
     * 
     * @return void
     */
    public function build(): void
    {
        try {
            if ($this->trace->hasRecordType('exception')) {
                $this->buildExceptionRender();
            } else {
                $this->buildRender();
            }

            $this->publish($this->lines);
        } catch (\Throwable $e) {
            // dont fail rendering
        }

        $this->lines = [];
    }

    /**
     * Build render output.
     * 
     * @return void
     */
    protected function buildRender(): void
    {
        $this->lines = [];

        $this->render($this->trace);
    }

    /**
     * Build render exception output.
     * 
     * @return void
     */
    protected function buildExceptionRender(): void
    {
        $this->lines = [];

        $this->renderException($this->trace);
    }

    /**
     * Render current Trace for specific target.
     * 
     * @param Trace $trace
     * @return void
     */
    abstract public function render(Trace $trace): void;

    /**
     * Render exception Trace for specific target.
     * 
     * @param Trace $trace
     * 
     * @return void
     */
    abstract public function renderException(Trace $trace): void;

    /**
     * Publish the rendered lines.
     * 
     * @return void
     */
    abstract public function publish(array $lines): void;
}
