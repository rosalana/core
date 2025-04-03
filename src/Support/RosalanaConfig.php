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
    
        [$returnStart, $returnEnd] = static::reset();
        
        $path = config_path('rosalana.php');
        $lines = file($path, FILE_IGNORE_NEW_LINES);
    
        foreach ($sections as $key => $section) {
            $rendered = static::render($section);
    
            // Vložení nové sekce před koncové ]; s 1 prázdným řádkem nad a pod
            array_splice($lines, $returnEnd, 0, $rendered);
            $returnEnd += count($rendered); // posunout index pro další vložení
        }
    
        file_put_contents($path, implode("\n", $lines));
        return true;
    }
    
    protected static function reset(): array
    {
        $path = config_path('rosalana.php');
    
        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: $path");
        }
    
        $resetContent = "<?php\n\nreturn [\n];\n";
    
        $text = file_put_contents($path, $resetContent);
        if ($text === false) {
            throw new \RuntimeException("Failed to reset config file: $path");
        }
        $lines = explode("\n", $resetContent);
        $start = collect($lines)->search(fn($line) => str_contains($line, 'return ['));
        $end = collect($lines)->search(fn($line) => trim($line) === '];');
    
        return [$start, $end];
    }
    
    protected static function render(RosalanaConfigSection $section): array
    {
        $lines = [];
    
        $lines[] = ""; // prázdný řádek nad sekcí
    
        if (!empty($section->getComment()['label']) || !empty($section->getComment()['description'])) {
            foreach (static::renderComment($section->getComment()) as $line) {
                $lines[] = $line;
            }
        }
    
        $lines[] = "    '{$section->getKey()}' => [";
        foreach ($section->getValues() as $key => $value) {
            $lines[] = "        '{$key}' => {$value},";
        }
        $lines[] = "    ],";
    
        return $lines;
    }
    
    protected static function renderComment(array $comment): array
    {
        $lines = [];
    
        $label = $comment['label'] ?? null;
        $description = $comment['description'] ?? null;
    
        if (!$label && !$description) return $lines;
    
        $lines[] = "    /*";
    
        if ($label) {
            $lines[] = '    ' . str_repeat('|', 1) . str_repeat('-', 74);
            $lines[] = '    | ' . $label;
            $lines[] = '    ' . str_repeat('|', 1) . str_repeat('-', 74);
        }
    
        if ($description) {
            $lines[] = "    |";
            foreach (array_chunk(explode(" ", $description), 10) as $line) {
                $lines[] = '    | ' . implode(" ", $line);
            }
            $lines[] = "    |";
        }
    
        $lines[] = "    */";
    
        return $lines;
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
