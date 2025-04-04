<?php

namespace Rosalana\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Rosalana\Core\Services\Basecamp\AppsService;
use Rosalana\Core\Services\Basecamp\Manager;

class RosalanaCoreServiceProvider extends ServiceProvider
{
    /**
     * Register everything in the container.
     */
    public function register()
    {
        // Merge default balíčkový config s configem hostitelské aplikace
        $this->mergeConfigFrom(__DIR__ . '/../../config/rosalana.php', 'rosalana');

        $this->app->singleton('rosalana.basecamp', function () {
            return new Manager();
        });

        $this->app->resolving('rosalana.basecamp', function (Manager $manager) {
            $manager->registerService('apps', new AppsService());
        });
    }

    /**
     * Boot services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Rosalana\Core\Console\Commands\PublishCommand::class,
            ]);
            $this->commands([
                \Rosalana\Core\Console\Commands\AddCommand::class,
            ]);
            $this->commands([
                \Rosalana\Core\Console\Commands\ListCommand::class,
            ]);
            $this->commands([
                \Rosalana\Core\Console\Commands\UpdateCommand::class,
            ]);
            $this->commands([
                \Rosalana\Core\Console\Commands\RosalanaCommnad::class,
            ]);
        }

        // Publikování configu, pokud chceš, aby si ho uživatel mohl zkopírovat
        $this->publishes([
            __DIR__ . '/../../config/rosalana.php' => config_path('rosalana.php'),
        ], 'rosalana-config');
    }
}
