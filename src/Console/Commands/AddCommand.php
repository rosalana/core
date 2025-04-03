<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Rosalana\Core\Console\InternalCommands;
use Rosalana\Core\Services\Package;
use Rosalana\Core\Support\RosalanaConfig;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class AddCommand extends Command
{
    use InternalCommands;
    use Colors;
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
            return 1;
        }

        $options = $notInstalled->mapWithKeys(function ($package) {
            return [$package->name => "$package->name ({$this->dim(Package::getDescription($package->name))})"];
        })->toArray();

        $selectedPackage = select(
            label: 'What package would you like to install?',
            options: $options,
            default: null,
        );

        $version = text(
            label: 'Would you like to install a specific version?',
        );

        if ($version === '') {
            $version = null;
        }

        $package = $notInstalled->first(function ($p) use ($selectedPackage) {
            return $p->name === $selectedPackage;
        });

        $processLabel = $version ? "Installing {$package->name} {$this->dim("($version)")}" : "Installing {$package->name}";

        spin(function () use ($package, $version) {
                $result = $package->install($version);

                if ($result->failed()) {
                    $this->line("\n");
                    echo $this->red($result->errorOutput());
                    exit(1);
                }
        }, $processLabel);


        RosalanaConfig::get('installed')->set($package->name, null)->save();

        // $this->updateConfig('installed', [
        //     $package->name => null
        // ]);

        $this->components->success("{$package->name} has been installed");
    }
}
