<?php

namespace Rosalana\Core\Services\Logging;

abstract class LogNode
{
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

    public function isStandAlone(): bool
    {
        return $this->standAlone;
    }
}
