<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

class File extends ParentNode
{
    protected string $path = '';

    public static function wrap(Collection $nodes): Collection
    {
        return $nodes;
    }

    public function padding(): int
    {
        return 0;
    }

    public function render(): array
    {
        $result = [];

        $this->nodes()->each(function ($node) use (&$result) {
            $rendered = $node->render();
            foreach ($rendered as $index => $content) {
                $result[$index] = $content;
            }
        });

        if (empty($result)) return $result;

        $maxIndex = max(array_keys($result));

        for ($i = 0; $i <= $maxIndex; $i++) {
            if (!array_key_exists($i, $result)) {
                $result[$i] = '';
            }
        }

        ksort($result);

        return $result;
    }

    public static function makeEmpty(string $name): static
    {
        if (str_ends_with($name, '.php')) {
            $name = substr($name, 4);
        }

        $instance = parent::makeEmpty("file:$name");

        $instance->created = false;
        $instance->path = config_path($instance->fullName());

        return $instance;
    }

    public function name(): string
    {
        return explode(':', $this->key(), 2)[1];
    }

    public function fullName(): string
    {
        return $this->name() . '.php';
    }

    public function setRaw(array $raw): self
    {
        $this->raw = $raw;
        $this->start = array_key_first($raw) ?? 0;
        $this->end = array_key_last($raw) ?? 0;

        return $this;
    }

    public function rename(string $name): self
    {
        return $this; // maybe in future
    }

    public function path(): string
    {
        return '';
    }

    public function lines(): array
    {
        return file($this->path, FILE_IGNORE_NEW_LINES);
    }

    public function insert(array $content): void
    {
        file_put_contents($this->path, implode(PHP_EOL, $content));
    }

    public function surroundingLines(): array
    {
        $lines = [];

        foreach ($this->lines() as $i => $line) {
            if (preg_match('/return\s*\[/i', $line)) {
                $lines['prefix'] = array_slice($this->lines(), 0, $i + 1);
                break;
            }
        }

        for ($i = array_key_last($this->lines()); $i >= 0; $i--) {
            if (preg_match('/\];\s*$/', trim($this->lines()[$i]))) {
                $lines['suffix'] = array_slice($this->lines(), $i);
                break;
            }
        }

        return $lines;
    }


    public function exists(): bool
    {
        return file_exists($this->path);
    }

    public function section(string $key): Section
    {
        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $instance = $this;

            foreach ($parts as $part) {
                if ($part === '') continue;
                $instance = $instance->section($part);
            }

            return $instance;
        }

        return parent::section($key);
    }
}
