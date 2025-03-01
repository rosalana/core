<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laravel\Prompts\Concerns\Colors;
use Rosalana\Core\Console\Packages;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    use Colors;
    use Packages;
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

        $installed = $this->installed();

        $options = collect($installed)->mapWithKeys(function ($version, $key) {
            return [$key => $key . ' ' . $this->cyan($version)];
        })->toArray();

        $package = select(
            label: 'What role should the user have?',
            options: $options,
            default: 'owner'
        );
        

        $this->info("You selected: $package");



        // get available packages
        // $packages = (new Filesystem)->getRequire(base_path('vendor/rosalana/core/src/Console/packages.php'));

        // // find installed packages
        // $installed = collect($packages)->mapWithKeys(function ($package) {
        //     return [$package => $this->hasComposerPackage($package)];
        // })->filter(function ($installed) {
        //     return $installed !== null;
        // });

        // $choices = [];
        // foreach ($installed as $package => $version) {
        //     // Výstup: "rosalana/core          0.3.3" s verzí zeleně
        //     $choices[] = sprintf('%-30s <fg=green>%s</>', $package, $version);
        // }

        // $selected = $this->choice('Vyberte balíček:', $choices);
        // $this->info("Vybral jste: $selected");





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


}
