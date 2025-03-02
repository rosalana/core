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

    public function addToEnv(string $value, ?string $after = null): void
    {
        $files = new Filesystem;

        if (! $files->exists(base_path('.env'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }

        // kontrola zda už value v .env neexistuje
        if (strpos(file_get_contents(base_path('.env')), $value) !== false) {
            return;
        }


        if ($after === null) {
            file_put_contents(
                base_path('.env'),
                PHP_EOL . $value . PHP_EOL,
            );
        } else {
            file_put_contents(
                base_path('.env'),
                preg_replace(
                    '/^' . preg_quote($after, '/') . '$/m',
                    $after . PHP_EOL . $value,
                    file_get_contents(
                        base_path('.env')
                    )
                )
            );
        }
    }
}
