<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Rosalana\Core\Services\Package;
use Rosalana\Core\Support\RosalanaConfig;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class RemoveCommand extends Command
{
    use Colors;

    /**
     * The name and signature of the console command.
     * 
     * @var string
     */
    protected $signature = 'rosalana:remove';

    /**
     * The console command description.
     * 
     * @var string
     */
    protected $description = 'Remove Rosalana package from your application';

    /**
     * Execute the console command.
     * 
     * @return void
     */
    public function handle()
    {
        $installed = Package::installed();

        dd(RosalanaConfig::read());

        if (env('APP_ENV') === 'production') {
            $this->components->error('You cannot remove packages in production');
            return 1;
        }

        $installed = $installed->filter(function ($package) {
            return $package->name !== 'rosalana/core';
        });

        if ($installed->isEmpty()) {
            $this->components->info('No packages installed');
            return 0;
        }

        $this->newLine();
        $this->line($this->bold('🗑️  Remove package'));
        $this->newLine();

        $options = $installed->mapWithKeys(function ($package) {
            return [$package->name => "$package->name ({$this->dim(Package::getDescription($package->name))})"];
        })->toArray();

        $selectedPackage = select(
            label: 'What package would you like to remove?',
            options: $options,
            default: null,
        );

        $package = $installed->first(function ($p) use ($selectedPackage) {
            return $p->name === $selectedPackage;
        });


        spin(function () use ($package) {
            $result = $package->uninstall();

            if ($result->failed()) {
                $this->line("\n");
                echo $this->red($result->errorOutput());
                exit(1);
            }
        }, "Removing {$package->name}...");


        if (RosalanaConfig::get('published')) {

        }

        $this->components->success("Package {$package->name} removed successfully");
    }
}
