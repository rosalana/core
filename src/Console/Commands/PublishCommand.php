<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Rosalana\Core\Console\InternalCommands;
use Rosalana\Core\PackageStatus;
use Rosalana\Core\Services\Package;
use Rosalana\Core\Support\RosalanaConfig;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class PublishCommand extends Command
{
    use Colors;
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
    protected $description = 'Publish files from a Rosalana package';

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
            return 1;
        }

        $options = $installed->mapWithKeys(function ($package) {
            $label = '';
            match ($package->status) {
                PackageStatus::NOT_PUBLISHED => $label = $package->name . $this->red(" ({$package->status->value})"),
                PackageStatus::OLD_VERSION => $label = $package->name . $this->dim(" $package->publishedVersion -> $package->installedVersion") . $this->yellow(" ({$package->status->value})"),
                PackageStatus::UP_TO_DATE => $label = $package->name . $this->dim(" $package->installedVersion") . $this->cyan(" ({$package->status->value})"),
            };
            return [$package->name => $label];
        })->toArray();


        $selectedPackage = select(
            label: 'What package would you like to publish?',
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
                })->prepend($this->cyan('Publish all'), 'all')
                ->filter(fn($label) => str_contains(strtolower($label), strtolower($value)))
                ->toArray(),
        );

        spin(function () use ($searchOptions, $publishOptions, $package) {
            if ($searchOptions === 'all') {
                $publishOptions->each(function ($option) {
                    $option['run']();
                });
            } else {
                $publishOptions[$searchOptions]['run']();
            }

            RosalanaConfig::get('published')
                ->set($package->name, var_export($package->installedVersion, true))
                ->save();
                
        }, "Publishing $searchOptions for $package->name");



        $this->components->success("Published $searchOptions for $package->name");
    }
}
