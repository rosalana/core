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

                    // RosalanaConfig::new('test')
                    //     ->add('url', "env('ROSALANA_BASECAMP_URL', 'http://localhost:8000')", 'Basecamp URL')
                    //     ->add('secret', "env('ROSALANA_APP_SECRET', 'secret')")
                    //     ->comment('Here you can define the settings...', 'Rosalana Test Settings')
                    //     ->save();


                    // RosalanaConfig::get('basecamp.url')->set('http://localhost:8000');

                    // tedy sekci můžu získat pomocí get (pokud neexistuje vytvoří se)
                    // potom můžu na každou sekci zavolat set, add, remove, comment, save

                    // Upraveje se to sekce po sekci a můžu získat hodnoty přímo z nějaké sekce. např. basecamp.url a nastavit set

                    // možná by se hodilo udělat resolve funkci zvášt od vytváření

                    // Takže vytváření by mohlo vypadat takto:
                    // RosalanaConfig::new('basecamp')
                    // ->add('url', env('ROSALANA_BASECAMP_URL', 'http://localhost:8000'), 'Basecamp URL')
                    // ->add('secret', env('ROSALANA_APP_SECRET', 'secret'))
                    // ->comment('Here you can define the settings for the Rosalana Auth. This settings are used for authorizate your app to the Rosalana Basecamp to use Basecamp services.', 'Rosalana Basecamp Auth Settings')
                    // ->save();

                    // No a pak by tedy get nevytvářel nový a failnul by nebo by vrátil null a nic by se nestalo nebo nevím









                    // RosalanaConfig::modify()
                    //     ->section('basecamp')
                    //     ->add('url', env('ROSALANA_BASECAMP_URL', 'http://localhost:8000'), 'Basecamp URL')

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
