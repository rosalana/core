<?php

namespace Rosalana\Core\Support;

class RosalanaConfig
{

    protected array $sections = [];

    public static function read(?string $path = null): static
    {
        $path ??= config_path('rosalana.php');

        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: $path");
        }

        $text = file_get_contents($path);
        $lines = explode("\n", $text);

        $instance = new static();

        foreach ($lines as $index => $line) {
            if (preg_match("/['\"](?<key>.+?)['\"]\s*=>\s*\[/", $line, $match)) {
                $key = $match['key'];
                $lineIndex = $index;

                // extract comment
                [$label, $description] = static::extractComment($lines, $lineIndex);

                // extract values from block
                $values = static::extractArrayValuesFromBlock($lines, $lineIndex);

                $instance->sections[$key] = new RosalanaConfigSection(
                    key: $key,
                    values: $values,
                    label: $label,
                    description: $description,
                    line: $lineIndex + 1,
                );
            }
        }

        return $instance;
    }

    /**
     * Create a new section in the configuration.
     * @param string $key
     * @return RosalanaConfigSection
     * @throws \RuntimeException
     */
    public static function new(string $key): RosalanaConfigSection
    {
        $instance = static::read();

        // if exists return it (not implemented yet)
        if (isset($instance->sections[$key])) {
            return $instance->sections[$key];
        }

        // otherwise create new one
        $section = new RosalanaConfigSection(
            key: $key,
            values: [],
            label: null,
            description: null,
            line: null,
        );

        $instance->sections[$key] = $section;
        return $section;
    }

    public static function get(string $key): ?RosalanaConfigSection
    {
        $instance = static::read();

        if (isset($instance->sections[$key])) {
            return $instance->sections[$key];
        }

        return null;
    }

    public static function save(?RosalanaConfigSection $section = null): bool
    {
        $sections = [];
        if ($section) {
            $sections = [$section->getKey() => $section];
        } else {
            $instance = static::read();
            $sections = $instance->sections;
        }
    
        $path = config_path('rosalana.php');
    
        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: $path");
        }
    
        $originalText = file_get_contents($path);
        $lines = explode("\n", $originalText);
    
        // Najdi index posledniho radku uvnitr return [ ... ]
        $returnStart = collect($lines)->search(fn($line) => str_contains($line, 'return ['));
        $returnEnd = collect($lines)->search(fn($line) => trim($line) === '];');

        if ($returnStart === false || $returnEnd === false) {
            throw new \RuntimeException("Invalid config file format: $path");
        }

        
        for ($i = $returnEnd - 1; $i > $returnStart; $i--) {
            if (trim($lines[$i]) === '') {
                array_splice($lines, $i, 1);
            }
        }
    
        foreach ($sections as $key => $section) {
            $rendered = static::render($section);
    
            // Najdi zacatek sekce v puvodnich lines
            $regex = "/['\"]" . preg_quote($key, '/') . "['\"]\s*=>\s*\[/";
            $startIndex = collect($lines)->search(fn($line) => preg_match($regex, $line));
    
            if ($startIndex === false && $returnEnd !== false) {
                // Sekce neexistuje, pridame ji pred koncove ];
                array_splice($lines, $returnEnd, 0, $rendered);
            } else {
                // Najdi konec bloku sekce
                $endIndex = $startIndex;
                $depth = 0;
                for ($i = $startIndex; $i < count($lines); $i++) {
                    if (str_contains($lines[$i], '[')) $depth++;
                    if (str_contains($lines[$i], ']')) $depth--;
                    if ($depth === 0 && $i !== $startIndex) {
                        $endIndex = $i;
                        break;
                    }
                }
    
                // Najdi zacatek komentare
                $commentStart = $startIndex;
                for ($i = $startIndex - 1; $i >= 0; $i--) {
                    if (str_starts_with(trim($lines[$i]), '/*')) {
                        $commentStart = $i;
                        break;
                    }
                }
    
                array_splice($lines, $commentStart, $endIndex - $commentStart + 1, $rendered);
            }
        }
    
        file_put_contents($path, implode("\n", $lines));
        return true;
    }
    
    protected static function render(RosalanaConfigSection $section): array
    {
        $lines = [];
    
        $lines[] = "\n"; // empty line before the section

        if (!empty($section->getComment()['label']) || !empty($section->getComment()['description'])) {
            $lines[] = static::renderComment($section->getComment());
        }
    
        $lines[] = "    '{$section->getKey()}' => [";
        foreach ($section->getValues() as $key => $value) {
            $lines[] = "        '{$key}' => {$value},";
        }
        $lines[] = "    ],";
        
        $lines[] = "\n"; // empty line after the section
    
        return $lines;
    }
    
    protected static function renderComment(array $comment): string
    {
        $label = $comment['label'] ?? null;
        $description = $comment['description'] ?? null;
    
        if (!$label && !$description) return '';
    
        $output = "    /*\n";
    
        if ($label) {
            $output .= '    ' . str_repeat('|', 1) . str_repeat('-', 74) . "\n";
            $output .= '    | ' . $label . "\n";
            $output .= '    ' . str_repeat('|', 1) . str_repeat('-', 74) . "\n";
        }
    
        if ($description) {
            $output .= "    |\n";
            foreach (array_chunk(explode(" ", $description), 10) as $line) {
                $output .= '    | ' . implode(" ", $line) . "\n";
            }
            $output .= "    |\n";
        }
    
        $output .= "    */";
    
        return $output;
    }

    protected static function extractComment(array $lines, int $lineIndex): array
    {
        $label = null;
        $description = [];
        $hasCommentBlock = false;
        $readingLabel = false;

        for ($i = $lineIndex - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);

            if (str_starts_with($line, '*/')) {
                $hasCommentBlock = true;
                continue;
            }

            if (!$hasCommentBlock) break;

            if (str_starts_with($line, '/*')) break;

            if (str_starts_with($line, '|')) {
                $content = trim(substr($line, 1));
                if (empty($content)) continue;

                if (str_starts_with($content, '-')) {
                    $readingLabel = true;
                    continue;
                }

                if ($readingLabel && !$label) {
                    $label = $content;
                } else {
                    $description[] = $content;
                }
            }
        }

        return [$label, implode("\n", array_reverse($description))];
    }

    protected static function extractArrayValuesFromBlock(array $lines, int $startLine): array
    {
        $values = [];
        $depth = 0;
        $start = null;

        for ($i = $startLine; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (str_contains($line, '[')) {
                if ($depth === 0) {
                    $start = $i + 1;
                }
                $depth++;
            }

            if (str_contains($line, ']')) {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
        }

        if (!isset($start)) {
            return [];
        }

        // extract values from the block
        for ($i = $start; $i < $i + 50; $i++) {
            if (!isset($lines[$i])) break;
            $line = trim($lines[$i]);

            if ($line === '],') break;
            if (!str_contains($line, '=>')) continue;

            if (preg_match("/['\"](?<key>.+?)['\"]\s*=>\s*(?<value>.*?),?\s*$/", $line, $match)) {
                $values[$match['key']] = rtrim($match['value'], ',');
            }
        }

        return $values;
    }
}
