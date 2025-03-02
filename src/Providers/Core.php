<?php

namespace Rosalana\Core\Providers;

use Rosalana\Core\Package;

class Core extends Package
{
    public function resolveName(): string
    {
        return 'rosalana/core';
    }

    public function resolvePublished(): bool
    {
        return file_exists(config_path('rosalana.php'));
    }

    public function publish(): void
    {
        // What to do when the package wanna be published
    }
}