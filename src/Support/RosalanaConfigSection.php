<?php
namespace Rosalana\Core\Support;

class RosalanaConfigSection
{
    protected $key;
    protected $values = [];
    protected $description = null;
    protected $label = null;

    protected $touched = false;

    public function __construct(string $key, array $values = [], ?string $label = null, ?string $description = null)
    {
        $this->key = $key;
        $this->values = $values;
        $this->label = $label;
        $this->description = $description;
    }

    public function add(string $key, mixed $value): static
    {
        if (!array_key_exists($key, $this->values)) {
            $this->values[$key] = $value;
            $this->touched = true;
        }

        return $this;
    }

    public function set(string $key, mixed $value): static
    {
        $this->values[$key] = $value;
        $this->touched = true;
        return $this;
    }

    public function remove(string $key): static
    {
        if (isset($this->values[$key])) {
            unset($this->values[$key]);
            $this->touched = true;
        }
        return $this;
    }

    public function comment(string $description, ?string $label = null): static
    {
        $this->description = $description;
        $this->label = $label ?? $this->label;
        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
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

    public function wasTouched(): bool
    {
        return $this->touched;
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
        ];
    }
}