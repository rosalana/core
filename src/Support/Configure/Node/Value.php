<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

class Value extends Node
{
    protected string|null $value = null;

    public function __construct(
        protected int $start,
        protected int $end,
        protected array $raw,
    ) {
        parent::__construct($start, $end, $raw);
    }

    public static function parse(array $content): Collection
    {
        $nodes = collect();
        $stack = [];

        $arrayStartRegex = '/^\s*([\'"])(?<key>[^\'"]+)\1\s*=>\s*\[\s*$/';

        $valueRegex = '/^\s*([\'"])(?<key>[^\'"]+)\1\s*=>\s*(?<value>.+?),\s*$/';

        foreach ($content as $index => $line) {

            $trim = trim($line);

            if (preg_match($arrayStartRegex, $line, $match)) {
                $stack[] = $match['key'];
                continue;
            }

            if ($trim === '],' || $trim === ']') {
                array_pop($stack);
                continue;
            }

            if (!preg_match($valueRegex, $line, $match)) {
                continue;
            }

            $key = $match['key'];
            $value = trim($match['value']);

            if (str_starts_with($value, '[') || str_starts_with($value, 'array(')) {
                if (str_contains($value, '=>')) {
                    continue;
                }
            }

            $fullKey = $stack
                ? implode('.', $stack) . '.' . $key
                : $key;

            $nodes->push(Value::make(
                start: $index,
                end: $index,
                raw: [$index => $line]
            )->setKey($fullKey)->set($value));
        }

        return $nodes;
    }

    public function render(): array
    {
        return [];
    }

    public function ensure(string $value): self
    {
        if (!$this->value) {
            $this->value = $value;
        }

        return $this;
    }

    public function set(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Přesunutí klíče do jiné sekce s udrženými daty. 
     * Value se použije pouze pokud v konfiguraci tento záznam nebyl.
     * Tedy jsem kopíroval NIC.
     */
    public function cut(Node|string|null $parent, string $value): self
    {
        return $this;
    }

    /**
     * Zkopírování klíče, který je v jiné sekci a stále udržet data uživatele.
     * Vrací tu zkopírovanou Value.
     * Path je path k sekci nebo prázdné když to má být root
     */
    public function copy(Node|string|null $parent, string $value): self
    {
        return $this;
    }

    public function withComment(string $label): void
    {
        //
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'key' => $this->key,
            'value' => $this->value,
        ]);
    }
}
