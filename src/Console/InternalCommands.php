<?php

use Illuminate\Support\Arr;


trait InternalCommands
{
    public function updateConfig(string $path, $value): void
    {
        // Například pokud máš konfiguraci v config/rosalana.php
        $file = config_path('rosalana.php');
    
        if (!file_exists($file)) {
            throw new \Exception("Config file {$file} does not exist.");
        }
    
        // Načtení konfigurace jako pole
        $config = include $file;
    
        // Nastavení nové hodnoty do pole na dané cestě
        Arr::set($config, $path, $value);
    
        // Převod pole do PHP kódu a zápis zpět do souboru
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($file, $content);
    }
}