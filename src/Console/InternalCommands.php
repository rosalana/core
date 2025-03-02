<?php

namespace Rosalana\Core\Console;

use Illuminate\Support\Arr;

trait InternalCommands
{
    function updateConfig(string $path, $value): void
    {
        // Například konfigurace v config/rosalana.php
        $configFile = config_path('rosalana.php');

        if (!file_exists($configFile)) {
            throw new \Exception("Config file {$configFile} does not exist.");
        }

        // Načtení konfigurace jako pole
        $config = include $configFile;

        // Nastavení nové hodnoty pro klíč 'installed'
        Arr::set($config, $path, $value);

        // Převod pole na PHP kód pomocí var_export
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";

        file_put_contents($configFile, $content);
    }
}
