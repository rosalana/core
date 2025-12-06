<?php

namespace Rosalana\Core\Support\Configure;

use Illuminate\Support\Collection;
use Rosalana\Core\Support\Configure\Node\File;

class Writer
{
    public function __construct(protected File $file)
    {
        if (!$this->file->exists()) {
            throw new \RuntimeException("Configuration file not found: {$this->file->fullName()}");
        }
    }

    public function write(): void
    {
        $render = $this->render($this->file->nodes());

        dd($render);
    }

    protected function render(Collection $nodes): array
    {
        $result = [];

        foreach ($nodes as $index => $node) {
            $result[$index] = $node->render();
        }

        $this->flatWithOriginalKeys($result);
        $this->pushEmptyLineToMissingIndex($result);

        return $result;
    }

    protected function flatWithOriginalKeys(array &$array): void
    {
        $result = [];

        $iterator = function ($value) use (&$result, &$iterator) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (is_array($v)) {
                        $iterator($v);
                        continue;
                    }

                    // Pokud index už existuje -> je to bug v render() některého nodeu
                    $result[$k] = $v;
                }
            }
        };

        $iterator($array);

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
