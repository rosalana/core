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

    /**
     * Add content to the section.
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function add(string $key, mixed $value): static
    {
        if (!$this->has($key)) {
            $this->values[$key] = $value;
        }

        return $this;
    }

    public function remove(string $key): static
    {
        if ($this->has($key)) {
            unset($this->values[$key]);
        }

        return $this;
    }

    /**
     * Hard set value to the section content.
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function set(string $key, mixed $value): static
    {
        $this->values[$key] = $value;
        return $this;
    }

    /**
     * Find out if the section has a key.
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * Add comment to the section.
     * @param string $description
     * @param string|null $label
     * @return static
     */
    public function comment(string $description, ?string $label = null): static
    {
        $this->description = $description;
        $this->label = $label ?? $this->label;
        return $this;
    }

    /**
     * Save the section to the configuration.
     * @return bool
     */
    public function save()
    {
        return RosalanaConfig::save($this);
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function filled(string $key): bool
    {
        return $this->has($key) && $this->values[$key] !== 'null' && $this->values[$key] !== '';
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

    /**
     * Convert the section to an array.
     */
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
