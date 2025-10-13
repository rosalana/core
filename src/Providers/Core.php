<?php

namespace Rosalana\Core\Providers;

use Illuminate\Support\Facades\Artisan;
use Rosalana\Core\Console\InternalCommands;
use Rosalana\Core\Contracts\Package;
use Rosalana\Core\Support\Config;

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

                    Config::new('published')
                        ->comment(
                            'List of all published Rosalana packages. This array is used to determine which packages have been installed and which version is currently active. This section is managed automatically. DO NOT EDIT THIS MANUALLY.',
                            'Published Packages'
                        )
                        ->save();

                    Config::new('basecamp')
                        ->add('url', "env('ROSALANA_BASECAMP_URL', 'http://localhost:8000')")
                        ->add('secret', "env('ROSALANA_APP_SECRET', 'secret')")
                        ->add('id', "env('ROSALANA_APP_ID', 'rosalana-app-01')")
                        ->add('name', "env('ROSALANA_APP_NAME', 'app-name-on-basecamp')")
                        ->add('version', "'v1'")
                        ->comment(
                            'Defines how your application connects to the central Rosalana Basecamp server, which manages shared data and communication across the ecosystem.',
                            'Basecamp Connection'
                        )
                        ->save();

                    Config::new('outpost')
                        ->add('connection', "'redis'")
                        ->add('queue', "'outpost'")
                        ->comment(
                            'Configuration for Rosalana Outpost, the global message broker used for asynchronous app-to-app communication. All packets are dispatched to Redis queues using this setup. Each application listens to its own dedicated queue based on this prefix.',
                            'Outpost Message Broker'
                        )
                        ->save();
                }
            ],
            'env' => [
                'label' => 'Publish .env variables',
                'run' => function () {
                    $this->setEnvValue('ROSALANA_BASECAMP_URL', 'http://localhost:8000');
                    $this->setEnvValue('ROSALANA_APP_SECRET');
                    $this->setEnvValue('ROSALANA_APP_ID', 'rosalana-app-');
                }
            ]
        ];
    }
}
