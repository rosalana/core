<?php

namespace Rosalana\Core\Support;

class RosalanaConfigSection
{
    protected $key;
    protected $values = [];
    protected $description = null;
    protected $label = null;
    protected $line = null;

    public function __construct(string $key, array $values = [], ?int $line = null, ?string $label = null, ?string $description = null)
    {
        $this->key = $key;
        $this->values = $values;
        $this->label = $label;
        $this->description = $description;
        $this->line = $line;
    }

    public function add(string $key, mixed $value): static
    {
        if (!$this->has($key)) {
            $this->values[$key] = $value;
        }

        return $this;
    }

    public function set(string $key, mixed $value): static
    {
        $this->values[$key] = $value;
        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function comment(string $description, ?string $label = null): static
    {
        $this->description = $description;
        $this->label = $label ?? $this->label;
        return $this;
    }

    public function save()
    {
        RosalanaConfig::save($this);
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getComment(): array
    {
        return [
            'label' => $this->label,
            'description' => $this->description,
        ];
    }

    // Napojení na config writer
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'values' => $this->values,
            'comment' => [
                'label' => $this->label,
                'description' => $this->description,
            ],
            'line' => $this->line,
        ];
    }
}
