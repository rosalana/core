<?php

namespace Rosalana\Core\Providers;

use Illuminate\Support\ServiceProvider;

class RosalanaCoreServiceProvider extends ServiceProvider
{
    /**
     * Register everything in the container.
     */
    public function register()
    {
        // Merge default balíčkový config s configem hostitelské aplikace
        $this->mergeConfigFrom(__DIR__.'/../../config/rosalana.php', 'rosalana');
    }

    /**
     * Boot services.
     */
    public function boot()
    {
        // Publikování configu, pokud chceš, aby si ho uživatel mohl zkopírovat
        $this->publishes([
            __DIR__.'/../../config/rosalana.php' => config_path('rosalana.php'),
        ], 'rosalana-config');

        // Případné další věci: load migrací, plugin routes, watchers, atd.
    }
}
