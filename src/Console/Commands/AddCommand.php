<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Rosalana\Core\Console\InternalCommands;
use Rosalana\Core\Services\Package;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class AddCommand extends Command
{
    use InternalCommands;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rosalana:add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Rosalana package to your application';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $notInstalled = Package::notInstalled();

        if (env('APP_ENV') === 'production') {
            $this->components->error('You cannot install packages in production');
            return;
        }

        $options = $notInstalled->mapWithKeys(function ($package) {
            return [$package->name => Package::getDescription($package->name)];
        })->toArray();

        $selectedPackage = select(
            label: 'What package would you like to install?',
            options: $options,
            default: null,
        );

        $package = $notInstalled->first(function ($p) use ($selectedPackage) {
            return $p->name === $selectedPackage;
        });

        spin(function () use ($package) {
            $package->install();
        }, "Installing {$package->name}");


        $this->updateConfig('installed', [
            $package->name => null
        ]);

        $this->components->success("{$package->name} has been installed");
    }
}
