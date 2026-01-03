<?php

namespace Rosalana\Core\Services\Logging;

abstract class LogNode
{
    protected bool $visible = true;

    protected bool $standAlone = true;

    public function __construct(public string $value, public ?string $name = null) {}

    public function getValue(): string
    {
        return $this->value;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isVisible(): bool
    {
        return $this->visible === true;
    }

    public function isStandAlone(): bool
    {
        return $this->standAlone;
    }

    public function hide(): void
    {
        $this->visible = false;
    }

    public function show(): void
    {
        $this->visible = true;
    }
}
