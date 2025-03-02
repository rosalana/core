<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Rosalana\Core\Console\InternalCommands;
use Rosalana\Core\PackageStatus;
use Rosalana\Core\Services\Package;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class PublishCommand extends Command
{
    use Colors;
    use InternalCommands;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rosalana:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the Rosalana package files';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        $installed = Package::installed();

        if (env('APP_ENV') === 'production') {
            $this->components->error('You cannot publish packages in production');
            return;
        }

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

        $publishOptions = collect($package->publish());

        $searchOptions = search(
            label: 'What would you like to publish?',
            options: fn(string $value) => $publishOptions
                ->mapWithKeys(function ($option, $key) {
                    return [$key => $option['label']];
                })->prepend('Publish all', 'all')
                ->filter(fn($label) => str_contains(strtolower($label), strtolower($value)))
                ->toArray(),
        );

        spin(function () use ($searchOptions, $publishOptions, $package) {
            sleep(3);
            if ($searchOptions === 'all') {
                $publishOptions->each(function ($option) {
                    $option['run']();
                });
            } else {
                $publishOptions[$searchOptions]['run']();
            }
            
            // update the installed version in the config
            $this->updateConfig('installed', [
                $package->name => $package->installedVersion
            ]);
        }, "Publishing $searchOptions for $package->name");



        $this->components->success("Published $searchOptions for $package->name");
    }
}
