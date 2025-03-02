<?php

namespace Rosalana\Core\Providers;

use Illuminate\Support\Facades\Artisan;
use Rosalana\Core\Console\InternalCommands;
use Rosalana\Core\Contracts\Package;

class Core implements Package
{
    use InternalCommands;

    public function resolvePublished(): bool
    {
        return file_exists(config_path('rosalana.php'));
    }

    public function publish(): void 
    {
        Artisan::call('vendor:publish', [
            '--provider' => "Rosalana\Core\Providers\RosalanaCoreServiceProvider",
            '--tag' => "rosalana-config"
        ]);
    }

    public function refresh(): void {}
}
