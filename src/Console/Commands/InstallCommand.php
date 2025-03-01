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
        // get available packages
        $packages = (new Filesystem)->getRequire(base_path('vendor/rosalana/core/src/Console/packages.php'));

        // find installed packages
        $installed = collect($packages)->mapWithKeys(function ($package) {
            return [$package => $this->hasComposerPackage($package)];
        });

        $installed->each(function ($version, $package) {
            if ($version) {
                $this->info("Package [{$package}] is already installed.");
            } else {
                $this->info("Package [{$package}] is not installed.");
            }
        });





        // show select menu for uninstalled packages
        // $packages = collect($installed)->filter(function ($installed) {
        //     return ! $installed;
        // })->keys();

        // if ($packages->isEmpty()) {
        //     $this->info('All Rosalana packages are already installed.');
        //     return;
        // }

        // $package = $this->choice('Which package would you like to install?', $packages->toArray());
    }

    protected function hasComposerPackage($package)
    {
        $packages = json_decode(file_get_contents(base_path('composer.json')), true);

        return $packages['require'][$package] ?? $packages['require-dev'][$package] ?? null;
    }
}
