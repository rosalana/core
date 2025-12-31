<?php

namespace Rosalana\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Rosalana\Core\Facades\Outpost;
use Rosalana\Core\Services\Basecamp\AppsService;
use Rosalana\Core\Services\Basecamp\TicketsService;
use Rosalana\Core\Services\Outpost\Message;

class RosalanaCoreServiceProvider extends ServiceProvider
{
    /**
     * Register everything in the container.
     */
    public function register()
    {
        // Merge default balíčkový config s configem hostitelské aplikace
        $this->mergeConfigFrom(__DIR__ . '/../../config/rosalana.php', 'rosalana');

        $this->app->bind('rosalana.basecamp', function () {
            return new \Rosalana\Core\Services\Basecamp\Manager();
        });

        $this->app->singleton('rosalana.context', function () {
            return new \Rosalana\Core\Services\App\Context();
        });

        $this->app->singleton('rosalana.app', function () {
            return new \Rosalana\Core\Services\App\Manager(
                new \Rosalana\Core\Services\App\Meta(),
                new \Rosalana\Core\Services\App\Hooks(),
            );
        });

        $this->app->singleton('rosalana.revizor', function () {
            return new \Rosalana\Core\Services\Revizor\Manager();
        });

        $this->app->singleton('rosalana.outpost', function () {
            return new \Rosalana\Core\Services\Outpost\Manager();
        });

        $this->app->singleton('rosalana.pipeline', function () {
            return new \Rosalana\Core\Services\Pipeline\Registry();
        });

        $this->app->resolving('rosalana.basecamp', function (\Rosalana\Core\Services\Basecamp\Manager $manager) {
            $manager->registerService('apps', new AppsService());
        });

        $this->app->resolving('rosalana.basecamp', function (\Rosalana\Core\Services\Basecamp\Manager $manager) {
            $manager->registerService('tickets', new TicketsService());
        });

        Outpost::receive('context.refresh:request', function (Message $message) {
            logger()->info('Received context.refresh request via Outpost from ' . $message->from);
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
                \Rosalana\Core\Console\Commands\AddCommand::class,
                \Rosalana\Core\Console\Commands\RemoveCommand::class,
                \Rosalana\Core\Console\Commands\ListCommand::class,
                \Rosalana\Core\Console\Commands\UpdateCommand::class,
                \Rosalana\Core\Console\Commands\RosalanaCommnad::class,
                \Rosalana\Core\Console\Commands\OutpostWorkCommand::class,
            ]);
        }

        // Publikování configu, pokud chceš, aby si ho uživatel mohl zkopírovat
        $this->publishes([
            __DIR__ . '/../../config/rosalana.php' => config_path('rosalana.php'),
        ], 'rosalana-config');
    }
}
