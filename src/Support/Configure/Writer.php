<?php

namespace Rosalana\Core\Support\Configure;

use Illuminate\Support\Collection;

class Writer
{
    public function __construct(protected string $file)
    {
        if (!file_exists($this->file)) {
            throw new \RuntimeException("Configuration file not found: {$this->file}");
        }
    }

    public function write(Collection $nodes): void
    {
        $render = $this->render($nodes);

        dd($render);
    }

    protected function render(Collection $nodes): array
    {
        $result = [];

        foreach ($nodes as $index => $node) {
            $result[$index] = $node->render();
        }

        $this->flatWithKeys($result);
        $this->pushEmptyLineToMissingIndex($result);

        return $result;
    }

    protected function flatWithKeys(array &$array): void
    {
        $result = [];

        array_walk_recursive($array, function ($value, $key) use (&$result) {
            $result[$key] = $value;
        });

        $array = $result;
    }

    protected function pushEmptyLineToMissingIndex(array &$array): void
    {
        $maxIndex = max(array_keys($array));

        for ($i = 0; $i <= $maxIndex; $i++) {
            if (!array_key_exists($i, $array)) {
                $array[$i] = '';
            }
        }

        ksort($array);
    }
}
