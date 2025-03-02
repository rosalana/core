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

        $this->addToEnv('JWT_SECRET=');
        $this->addToEnv('ROSALANA_BASECAMP_URL=http://localhost:8000');
        $this->addToEnv('ROSALANA_APP_SECRET=');
    }

    public function refresh(): void {}
}
