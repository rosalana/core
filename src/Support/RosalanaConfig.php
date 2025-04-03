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

        $values = include $path;

        if (!is_array($values)) {
            throw new \RuntimeException("Invalid config file structure: $path");
        }

        $text = file_get_contents($path);
        $lines = explode("\n", $text);

        $instance = new static();

        foreach ($values as $key => $sectionValues) {
            $regex = "/['\"]" . preg_quote($key, '/') . "['\"]\s*=>\s*\[/";
            $lineIndex = collect($lines)->search(fn($line) => preg_match($regex, $line));

            $label = null;
            $description = [];

            $readingLabel = false;

            if ($lineIndex !== false) {
                $hasCommentBlock = false;
                for ($i = $lineIndex - 1; $i >= 0; $i--) {
                    $line = trim($lines[$i]);

                    if (str_starts_with($line, '*/')) {
                        $hasCommentBlock = true;
                        continue;
                    }

                    if (!$hasCommentBlock) {
                        break; // no comment on this block
                    }

                    if (str_starts_with($line, '/*')) break;

                    if (str_starts_with($line, '|')) {
                        $content = trim(substr($line, 1));

                        if (empty($content)) {
                            continue;
                        }

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
                $description = array_reverse($description);
            }

            $instance->sections[$key] = new RosalanaConfigSection(
                key: $key,
                values: $sectionValues,
                label: $label,
                description: implode("\n", $description),
                line: $lineIndex,
            );
        }

        return $instance;
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
        // $output = $this->render();
        // $path = config_path('rosalana.php');

        // if (file_exists($path)) {
        //     $currentContent = file_get_contents($path);
        //     if ($currentContent !== $output) {
        //         file_put_contents($path, $output);
        //     }
        // } else {
        //     file_put_contents($path, $output);
        // }

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
