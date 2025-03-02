<?php

namespace Rosalana\Core\Providers;

use Illuminate\Support\Collection;
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

    public function publish(): array
    {
        return [
            'config' => [
                'label' => 'Publish rosalana.php config file',
                'run' => function () {
                    Artisan::call('vendor:publish', [
                        '--provider' => "Rosalana\Core\Providers\RosalanaCoreServiceProvider",
                        '--tag' => "rosalana-config"
                    ]);
                }
            ],
            'env' => [
                'label' => 'Set default environment variables',
                'run' => function () {
                    $this->setEnvValue('JWT_SECRET');
                    $this->setEnvValue('ROSALANA_BASECAMP_URL', 'http://localhost:8000');
                    $this->setEnvValue('ROSALANA_APP_SECRET');
                }
            ]
        ];
    }

    public function refresh(): void {}
}
