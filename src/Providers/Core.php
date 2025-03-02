<?php

namespace Rosalana\Core\Providers;

use Rosalana\Core\Contracts\Package;

class Core implements Package
{
    public function resolvePublished(): bool
    {
        return file_exists(config_path('rosalana.php'));
    }

    public function publish(): void
    {
        // What to do when the package wanna be published
    }
}