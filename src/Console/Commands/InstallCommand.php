<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laravel\Prompts\Concerns\Colors;
use PhpParser\Node\Expr\FuncCall;
use Rosalana\Core\PackageStatus;
use Rosalana\Core\Services\Package;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    use Colors;
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

        $options = Package::all()->mapWithKeys(function ($package) {
            $label = '';
            match ($package->status) {
                PackageStatus::NOT_PUBLISHED => $label = $this->red("$package->name ({$package->status->value})"),
                PackageStatus::OLD_VERSION => $label = $this->yellow("$package->name ({$package->status->value} $package->publishedVersion -> $package->installedVersion)"),
                PackageStatus::UP_TO_DATE => $label = $this->cyan("$package->name ({$package->status->value} $package->installedVersion)"),
                PackageStatus::NOT_INSTALLED => $label = $this->red("$package->name ({$package->status->value})"),
            };
            return [$package->name => $label];
        })->toArray();


        $package = select(
            label: 'What package would you like to install?',
            options: $options,
            default: null,
        );

        $this->info("You selected: $package");

        // dump($options);


        // $installed = $this->installed();

        // $published = $this->published();

        // $options = $published->mapWithKeys(function ($version, $key) {
        //     return [$key => $key . ' ' . $version];
        // })->toArray();

        // $package = select(
        //     label: 'What package would you like to install?',
        //     options: $options,
        //     default: null,
        // );
        

        // $this->info("You selected: $package");



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
