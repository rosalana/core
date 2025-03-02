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

        $installed = Package::installed();

        $options = $installed->mapWithKeys(function ($package) {
            $label = '';
            match ($package->status) {
                PackageStatus::NOT_PUBLISHED => $label = $package->name . $this->red(" ({$package->status->value})"),
                PackageStatus::OLD_VERSION => $label = $package->name . $this->yellow(" ({$package->status->value} $package->publishedVersion -> $package->installedVersion)"),
                PackageStatus::UP_TO_DATE => $label = $package->name . $this->cyan(" ({$package->status->value} $package->installedVersion)"),
            };
            return [$package->name => $label];
        })->toArray();


        $selectedPackage = select(
            label: 'What package would you like to install?',
            options: $options,
            default: null,
        );

        $package = $installed->first(function ($p) use ($selectedPackage) {
            return $p->name === $selectedPackage;
        });

        dump($package);
    }
}
