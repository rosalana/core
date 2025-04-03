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

            $readingHeader = false;

            if ($lineIndex !== false) {
                for ($i = $lineIndex - 1; $i >= 0; $i--) {
                    $line = trim($lines[$i]);

                    

                    if (!str_starts_with($line, '*/')) break; // neexistuje komentář

                    // if ($line = '|' || $line) {
                    //     continue; // prázdný řádek
                    // }

                    dump($line);
                    if (str_starts_with($line, '/*')) break; // konec komentáře


                    // if (str_starts_with($line, '|')) {
                    //     $content = trim(substr($line, 1));
                    //     dump($content);





                        // if (!$label && !str_starts_with($content, '-')) {
                        //     $label = $content;
                        // } else {
                        //     $description[] = $content;
                        // }

                        // if (str_starts_with($line, '/*')) break;
                    // }
                }

                $description = array_reverse($description);
            }

            $instance->sections[$key] = new RosalanaConfigSection(
                key: $key,
                values: $sectionValues,
                label: $label,
                description: implode("\n", $description)
            );
        }

        return $instance;
    }


    /**
     * Add a new section to the config.
     * 
     * @param string $key Root-level key for the section.
     * @param array $values Array of values for the section.
     * @param string|null $description Optional comment for the section.
     * @param string|null $label Optional comment label for the section.
     * @return $this
     */
    public static function new(string $key, array $values, ?string $description = null, ?string $label = null)
    {
        self::$sections[$key] = [
            'values' => $values,
            'description' => $description,
            'label' => $label,
        ];

        return new static();
    }

    public function all(): array
    {
        return $this->sections;
    }

    public function getComment(string $key): array
    {
        return $this->sections[$key]['description'] ?? [];
    }

    public function merge(RosalanaConfig $other): static
    {
        foreach ($other->all() as $key => $section) {
            $this->sections[$key] = $section;
        }

        return $this;
    }

    public function save()
    {
        $output = $this->render();
        $path = config_path('rosalana.php');

        if (file_exists($path)) {
            $currentContent = file_get_contents($path);
            if ($currentContent !== $output) {
                file_put_contents($path, $output);
            }
        } else {
            file_put_contents($path, $output);
        }

        return $this;
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
