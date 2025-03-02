<?php

namespace Rosalana\Core\Console;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Filesystem\Filesystem;

trait InternalCommands
{
    use InteractsWithIO;

    public function updateConfig(string $key, array $value): void
    {
        $files = new Filesystem();
        $configFile = config_path('rosalana.php');

        if (!$files->exists($configFile)) {
            throw new \Exception("Konfigurační soubor {$configFile} neexistuje.");
        }

        $contents = $files->get($configFile);

        // Regulární výraz hledá definici klíče, např.:
        // 'installed' => [ ... ]
        $pattern = "/(['\"])" . preg_quote($key, '/') . "\\1\s*=>\s*(\[[^\]]*\])/s";

        if (preg_match($pattern, $contents, $matches)) {
            // $matches[2] obsahuje řetězec reprezentující pole, např. "[ 'rosalana/core' => 'dev-master', ]"
            $currentArray = [];
            $code = 'return ' . $matches[2] . ';';
            try {
                $currentArray = eval($code);
            } catch (\Throwable $e) {
                // Pokud eval selže, předpokládáme prázdné pole
                $currentArray = [];
            }
            if (!is_array($currentArray)) {
                $currentArray = [];
            }
            // Sloučíme existující hodnoty s novými; nové hodnoty přepíší staré pro stejné klíče
            $merged = array_merge($currentArray, $value);

            // Vytvoříme nový řetězec reprezentující pole – lze volit krátkou syntaxi, zde var_export vrací "array(...)".
            $newArrayContent = var_export($merged, true);

            // Nahrazujeme původní definici klíče novou definicí.
            $replacement = "$1{$key}$1 => " . $newArrayContent;
            $newContents = preg_replace($pattern, $replacement, $contents);

            if ($newContents === null) {
                throw new \Exception("Chyba při aktualizaci konfigurace.");
            }

            $files->put($configFile, $newContents);
        } else {
            $newEntry = "    '{$key}' => " . var_export($value, true) . ",\n";
            $pattern = "/(\n\s*\]\s*;)/s";
            $newContents = preg_replace($pattern, $newEntry . "$1", $contents);
            if ($newContents === null) {
                throw new \Exception("Chyba při přidávání nového klíče do konfigurace.");
            }
            $files->put($configFile, $newContents);
        }
    }

    public function setEnvValue(string $key, ?string $value = null): void
    {
        $files = new Filesystem();
        $envFile = base_path('.env');

        if (!$files->exists($envFile)) {
            throw new \Exception("Soubor .env neexistuje na cestě: {$envFile}");
        }

        $envContent = $files->get($envFile);

        $pattern = "/^{$key}=.*/m";
        $newLine = "{$key}={$value}";

        if (preg_match($pattern, $envContent)) {
            if ($value === null) {
                return;
            }
            $envContent = preg_replace($pattern, $newLine, $envContent);
        } else {
            $envContent .= "\n" . $newLine;
        }

        $files->put($envFile, $envContent);
    }

    public function publishFile(string $from, string $to): void
    {
        //
    }
}
