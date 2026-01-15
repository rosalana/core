<?php

namespace Rosalana\Core\Console;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Filesystem\Filesystem;

trait InternalCommands
{
    use InteractsWithIO;

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
}
