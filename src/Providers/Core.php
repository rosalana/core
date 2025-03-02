<?php

namespace Rosalana\Core\Providers;

use Illuminate\Support\Facades\Artisan;
use Rosalana\Core\Contracts\Package;

class Core implements Package
{
    public function resolvePublished(): bool
    {
        return file_exists(config_path('rosalana.php'));
    }

    public function publish(): void 
    {
        Artisan::call('vendor:publish', [
            '--provider' => 'Rosalana\\Core\\RosalanaCoreServiceProvider',
            '--force'    => true,
        ]);
    }

    public function refresh(): void {}
}
