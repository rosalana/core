<?php

namespace Rosalana\Core\Support\Configure;

use Rosalana\Core\Support\Configure\Node\ArrayBlock;
use Rosalana\Core\Support\Configure\Node\File;
use Rosalana\Core\Support\Configure\Node\RichComment;
use Rosalana\Core\Support\Configure\Node\Section;
use Rosalana\Core\Support\Configure\Node\Value;

class Reader
{
    public function __construct(protected File $file)
    {
        if (!$this->file->exists()) {
            throw new \RuntimeException("Configuration file not found: {$this->file->fullName()}");
        }
    }

    public function read(): File
    {
        $content = $this->content();

        $this->file->setRaw($content);

        $parse = collect()
            ->merge(RichComment::parse($content))
            ->merge(Value::parse($content))
            ->merge(ArrayBlock::parse($content));

        $sorted = $parse->sortBy->start()->values();

        $sections = Section::parse($sorted->toArray());

        $sections->each(fn ($node) => $this->file->addChild($node));

        return $this->file;
    }

    public function content(): array
    {
        $lines = $this->file->lines();

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
