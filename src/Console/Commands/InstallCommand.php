<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rosalana:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Rosalana packages';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // get info from the ./installed.php file
        $installed = (new Filesystem)->getRequire(base_path('vendor/rosalana/core/src/Console/installed.php'));

        // show select menu for uninstalled packages
        $packages = collect($installed)->filter(function ($installed) {
            return ! $installed;
        })->keys();

        if ($packages->isEmpty()) {
            $this->info('All Rosalana packages are already installed.');
            return;
        }

        $package = $this->choice('Which package would you like to install?', $packages->toArray());
    }
}
