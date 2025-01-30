<?php

namespace Rosalana\Core\Providers;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register()
    {
        // Sloučíme config z balíčku s configem aplikace (pod klíč "rosalana")
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/rosalana.php', 'rosalana'
        );
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        // Publikování configu (pokud chceš, aby si ho hlavní aplikace mohla zkopírovat)
        $this->publishes([
            __DIR__ . '/../../config/rosalana.php' => config_path('rosalana.php'),
        ], 'rosalana-config');

        // Případné další kroky, jako:
        // - publikování migrací,
        // - registrace event listenerů,
        // - atd.
    }
}
