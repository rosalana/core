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

                // 🔍 Extrahuj komentář
                [$label, $description] = static::extractComment($lines, $lineIndex);

                // 🧠 Získej hodnoty sekce jako raw stringy
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

        // Teď máme řádky mezi [...], zkusíme vyparsovat jednotlivé páry
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

    public static function save(RosalanaConfigSection $section)
    {
        $path = config_path('rosalana.php');

        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: $path");
        }

        $text = file_get_contents($path);

        $lines = explode("\n", $text);
        $lineIndex = $section->getLine();

        dump($section->toArray());
        return $section;
    }

    protected function render(): string
    {
        $output = "<?php\n\nreturn [\n";

        foreach ($this->sections as $key => $section) {
            $comment = $this->renderComment($section);
            if ($comment) {
                // odsazení komentářů
                $output .= "\n" . preg_replace('/^/m', '    ', $comment) . "\n";
            }

            $valueExport = var_export($section['values'], true);
            // zarovnej export na nový řádek s indentací
            $valueExport = preg_replace('/^/m', '        ', $valueExport);

            $output .= "    '{$key}' => {$valueExport},\n";
        }

        $output .= "];\n";

        return $output;
    }


    protected function renderComment(array $section): string
    {
        $label = $section['label'] ?? null;
        $comment = $section['description'] ?? null;

        if (!$label && !$comment) return '';

        $output = "/*\n";

        if ($label) {
            $output .= str_repeat('|', 1) . str_repeat('-', 74) . "\n";
            $output .= '| ' . $label . "\n";
            $output .= str_repeat('|', 1) . str_repeat('-', 74) . "\n";
        }

        if ($comment) {
            $output .= "|\n";
            foreach (array_chunk(explode(" ", $comment), 10) as $line) {
                $output .= '| ' . implode(" ", $line) . "\n";
            }
            $output .= "|\n";
        }

        $output .= "*/";

        return $output;
    }
}
