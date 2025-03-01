<?php

namespace Rosalana\Core\Providers;

class EnsurePublished
{
    /**
     * Ensure the package is published and return boolean.
     */
    public function __invoke()
    {
        if (! file_exists(config_path('rosalana.php'))) {
            return false;
        }

        return true;
    }
}