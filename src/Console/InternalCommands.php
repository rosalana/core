<?php

namespace Rosalana\Core\Console;

use Illuminate\Filesystem\Filesystem;

trait InternalCommands
{
    public function updateConfig(string $key, $value): void
    {
        $configFile = config_path('rosalana.php');

        if (!file_exists($configFile)) {
            throw new \Exception("Config file {$configFile} does not exist.");
        }

        $contents = file_get_contents($configFile);

        // Připravíme novou část pro 'installed'
        // (Předpokládáme, že chceme odsazení 4 mezery pro tento klíč.)
        if ($key === 'installed' && is_array($value)) {
            $newArrayContent = "[\n";
            foreach ($value as $k => $v) {
                // Upravíme odsazení (4+4=8 mezer) – upravte dle vlastních preferencí
                $newArrayContent .= "        '" . addslashes($k) . "' => '" . addslashes($v) . "',\n";
            }
            $newArrayContent .= "    ]";
        } else {
            // Můžeš doplnit podporu pro jiné klíče, pokud je to potřeba.
            $newArrayContent = var_export($value, true);
        }

        // Regulární výraz hledá klíč 'installed' následovaný => a hranatými závorkami (obsahem uvnitř)
        // Používáme /s flag, aby tečka odpovídala i novým řádkům.
        $pattern = "/('installed'\s*=>\s*)\[[^\]]*\]/s";

        $replacement = "$1" . $newArrayContent;

        $newContents = preg_replace($pattern, $replacement, $contents);

        if ($newContents === null) {
            throw new \Exception("Chyba při aktualizaci konfigurace.");
        }

        file_put_contents($configFile, $newContents);
    }

    function setEnvValue(string $key, ?string $value = null): void
    {
        $files = new Filesystem();
        $envFile = base_path('.env');
    
        if (!$files->exists($envFile)) {
            throw new \Exception("Soubor .env neexistuje na cestě: {$envFile}");
        }
    
        $envContent = $files->get($envFile);
    
        // Regulární výraz pro hledání řádku s daným klíčem (multiline mód)
        $pattern = "/^{$key}=.*/m";
        $newLine = "{$key}={$value}";
    
        if (preg_match($pattern, $envContent)) {
            if ($value === null) {
                return;
            }
            // Pokud klíč již existuje, nahradí jeho hodnotu
            $envContent = preg_replace($pattern, $newLine, $envContent);
        } else {
            // Pokud klíč neexistuje, přidá nový řádek na konec souboru
            $envContent .= "\n" . $newLine . "\n";
        }
    
        $files->put($envFile, $envContent);
    }
}
