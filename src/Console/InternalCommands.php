<?php

namespace Rosalana\Core\Console;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\VarExporter\VarExporter;

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
            // $matches[2] obsahuje řetězec s existujícím polem
            $currentArray = [];
            $code = 'return ' . $matches[2] . ';';
            try {
                $currentArray = eval($code);
            } catch (\Throwable $e) {
                $currentArray = [];
            }
            if (!is_array($currentArray)) {
                $currentArray = [];
            }
            // Nové hodnoty přepíší existující pro stejné klíče
            $merged = array_merge($currentArray, $value);
            // Exportujeme pole pomocí VarExporter (krátká syntaxe s hranatými závorkami)
            $exported = VarExporter::export($merged);
            $replacement = "$1{$key}$1 => " . $exported;
            $newContents = preg_replace($pattern, $replacement, $contents);
            if ($newContents === null) {
                throw new \Exception("Chyba při aktualizaci konfigurace.");
            }
        } else {
            // Pokud klíč neexistuje, vložíme nový záznam před uzavírací závorku pole
            $exported = VarExporter::export($value);
            $newEntry = "    '{$key}' => " . $exported . ",\n";
            $pattern = "/(\n\s*\]\s*;)/s";
            $newContents = preg_replace($pattern, $newEntry . "$1", $contents);
            if ($newContents === null) {
                throw new \Exception("Chyba při přidávání nového klíče do konfigurace.");
            }
        }
        $files->put($configFile, $newContents);
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
