<?php

namespace Rosalana\Core\Providers;

use Rosalana\Core\Package;

class Core extends Package
{
    public function name(): string
    {
        return 'rosalana/core';
    }

    public function published(): bool
    {
        return file_exists(config_path('rosalana.php'));
    }

    public function install(): void
    {
        // What to do when the package is installed
    }
}