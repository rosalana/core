<?php

namespace Rosalana\Core\Providers;

use Illuminate\Support\Facades\Artisan;
use Rosalana\Core\Console\InternalCommands;
use Rosalana\Core\Contracts\Package;
use Rosalana\Core\Support\RosalanaConfig;

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

                    dump(RosalanaConfig::read());

                    // RosalanaConfig::make()
                    //     ->addSection('basecamp', [
                    //         'url' => env('ROSALANA_BASECAMP_URL', 'http://localhost:8000'),
                    //         'secret' => env('ROSALANA_APP_SECRET', 'secret'),
                    //     ], 'Here you can define the settings for the Rosalana Auth. This settings are used for authorizate your app to the Rosalana Basecamp to use Basecamp services.', 'Rosalana Basecamp Auth Settings')
                    //     ->addSection('installed', [], 'All installed Rosalana packages. This array is used to determine if the package has been installed or not and with the correct version. DO NOT MODIFY THIS ARRAY MANUALLY!', 'Rosalana Core Installed')
                    //     ->save();
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
