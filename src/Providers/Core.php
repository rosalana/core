<?php

namespace Rosalana\Core\Providers;

use Illuminate\Support\Facades\Artisan;
use Rosalana\Core\Console\InternalCommands;
use Rosalana\Core\Contracts\Package;
use Rosalana\Core\Support\ConfigBuilder;

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

                    ConfigBuilder::new('published')
                        ->comment(
                            'List of all published Rosalana packages. This array is used to determine which packages have been installed and which version is currently active. This section is managed automatically. DO NOT EDIT THIS MANUALLY.',
                            'Published Packages'
                        )
                        ->save();

                    ConfigBuilder::new('basecamp')
                        ->add('url', "env('ROSALANA_BASECAMP_URL', 'http://localhost:8000')")
                        ->add('secret', "env('ROSALANA_APP_SECRET', 'secret')")
                        ->add('version', "'v1'")
                        ->comment(
                            'Defines how your application connects to the central Rosalana Basecamp server, which manages shared data and communication across the ecosystem.',
                            'Basecamp Connection'
                        )
                        ->save();
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
}
