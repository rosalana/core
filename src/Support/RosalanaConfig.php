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

        // Safe include config file
        $data = include $path;

        if (!is_array($data)) {
            throw new \RuntimeException("Invalid config file structure: $path");
        }

        $instance = new static();
        $instance->sections = $data;

        return $instance;
    }

    public static function make()
    {
        return new static();
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
    public function addSection(string $key, array $values, ?string $description = null, ?string $label = null)
    {
        $this->sections[$key] = [
            'values' => $values,
            'description' => $description,
            'label' => $label,
        ];

        return $this;
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
