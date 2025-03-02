<?php

namespace Rosalana\Core\Console;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Filesystem\Filesystem;

trait InternalCommands
{
    use InteractsWithIO;

    public function updateConfig(string $key, $value): void
    {
        $configFile = config_path('rosalana.php');

        if (!file_exists($configFile)) {
            $this->components->error('The rosalana.php config file does not exist. Please publish it.');
            return;
        }

        $contents = file_get_contents($configFile);

        if ($key === 'installed' && is_array($value)) {
            $newArrayContent = "[\n";
            foreach ($value as $k => $v) {
                $newArrayContent .= "        '" . addslashes($k) . "' => '" . addslashes($v) . "',\n";
            }
            $newArrayContent .= "    ]";
        } else {
            $newArrayContent = var_export($value, true);
        }

        $pattern = "/('installed'\s*=>\s*)\[[^\]]*\]/s";

        $replacement = "$1" . $newArrayContent;

        $newContents = preg_replace($pattern, $replacement, $contents);

        if ($newContents === null) {
            throw new \Exception("Chyba při aktualizaci konfigurace.");
        }

        file_put_contents($configFile, $newContents);
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

    public function publishFiles(string $from, string $to): void
    {
        //
    }
}
