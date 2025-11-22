<?php

namespace Rosalana\Core\Support\Configure;

use Illuminate\Support\Collection;
use Rosalana\Core\Support\Configure\Node\RichComment;
use Rosalana\Core\Support\Configure\Node\Value;

class Reader
{
    public function __construct(protected string $file)
    {
        if (!file_exists($this->file)) {
            throw new \RuntimeException("Configuration file not found: {$this->file}");
        }
    }

    public function read(): Collection
    {
        $content = $this->content();

        return Section::from(collect()
            ->merge(RichComment::parse($content))
            ->merge(Value::parse($content))
            ->sortBy->startLine()
            ->values());
    }

    public function content(): array
    {
        $lines = file($this->file, FILE_IGNORE_NEW_LINES);

        $start = null;
        $end = null;

        foreach ($lines as $i => $line) {
            if (preg_match('/return\s*\[/i', $line)) {
                $start = $i;
                break;
            }
        }

        for ($i = array_key_last($lines); $i >= 0; $i--) {
            if (preg_match('/\];\s*$/', trim($lines[$i]))) {
                $end = $i;
                break;
            }
        }

        if ($start === null || $end === null) {
            throw new \RuntimeException("Could not locate return block in config");
        }

        return array_slice($lines, $start + 1, $end - $start - 1, true);
    }
}
