<?php

namespace Rosalana\Core\Providers;

use Illuminate\Support\Facades\Artisan;
use Rosalana\Configure\Configure;
use Rosalana\Core\Console\InternalCommands;
use Rosalana\Core\Contracts\Package;

class Core implements Package
{
    use InternalCommands;

    public function resolvePublished(): bool
    {
        return Configure::fileExists('rosalana');
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

                    Configure::file('rosalana')
                        ->section('.published')
                        ->withComment(
                            'Published Rosalana Packages',
                            "List of all published Rosalana packages. \nThis array is used to determine which packages have been installed and which version is currently active. \nThis section is managed automatically. \n\nDO NOT EDIT THIS MANUALLY."
                        )

                        ->section('.basecamp')
                        ->withComment(
                            'Basecamp Connection',
                            "Defines how your application connects to the central Rosalana Basecamp server, \nwhich manages shared data and communication across the ecosystem."
                        )
                        ->value('url', "env('ROSALANA_BASECAMP_URL', 'http://localhost:8000')")
                        ->value('secret', "env('ROSALANA_APP_SECRET', 'secret')")
                        ->value('id', "env('ROSALANA_APP_ID', 'rosalana-app-01')")
                        ->value('name', "env('ROSALANA_APP_NAME', 'app-name-on-basecamp')")
                        ->value('version', "v1")

                        ->section('.outpost')
                        ->withComment(
                            'Outpost Message Broker',
                            "Configuration for Rosalana Outpost, the global message broker used for asynchronous app-to-app communication. \nAll packets are dispatched to Redis queues using this setup. Each application listens to \nits own dedicated queue based on this prefix.",
                        )
                        ->value('connection', "outpost")
                        ->value('listeners', "'App\\Outpost\\'")

                        ->section('.revizor')
                        ->withComment(
                            'Revizor Authentication',
                            "Configuration for Revizor, the authentication system for \nA2A communication within the Rosalana ecosystem.",
                        )
                        ->value('active_tickets', "config('rosalana.basecamp.url') . '.well-known/tickets'")
                        ->value('signature_ttl', '60')
                        ->value('cache_prefix', "revizor_signatures_")

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
