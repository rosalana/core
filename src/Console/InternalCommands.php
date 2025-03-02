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
            // Získáme existující pole – pomocí eval (pouze pokud máte kontrolu nad obsahem souboru)
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
            // Sloučíme existující pole s novými hodnotami (nové mají prioritu)
            $merged = array_merge($currentArray, $value);
            // Exportujeme pole pomocí VarExporter – vygeneruje výstup ve formátu s hranatými závorkami
            $exported = VarExporter::export($merged);
            // Přeformátujeme exportované pole tak, aby vnitřní řádky byly odsazené o 8 mezer.
            $lines = explode("\n", $exported);
            if (count($lines) >= 3) {
                $firstLine = array_shift($lines);  // první řádek (např. "[")
                $lastLine  = array_pop($lines);      // poslední řádek (např. "]")
                $middleLines = array_map(function ($line) {
                    return "    " . $line;
                }, $lines);
                $formattedExported = $firstLine . "\n" . implode("\n", $middleLines) . "\n" . "    " . $lastLine;
            } else {
                $formattedExported = $exported;
            }
            $replacement = "$1{$key}$1 => " . $formattedExported;
            $newContents = preg_replace($pattern, $replacement, $contents);
            if ($newContents === null) {
                throw new \Exception("Chyba při aktualizaci konfigurace.");
            }
        } else {
            // Klíč nebyl nalezen – vložíme nový záznam před uzavírací závorku hlavního pole.
            $exported = VarExporter::export($value);
            $lines = explode("\n", $exported);
            if (count($lines) >= 3) {
                $firstLine = array_shift($lines);
                $lastLine  = array_pop($lines);
                $middleLines = array_map(fn($line) => "    " . $line, $lines);
                $formattedExported = $firstLine . "\n" . implode("\n", $middleLines) . "\n" . "    " . $lastLine;
            } else {
                $formattedExported = $exported;
            }
            $newEntry = "\n    '{$key}' => " . $formattedExported . ",\n";
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
