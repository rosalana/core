<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Rosalana\Core\Services\Package;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class UpdateCommand extends Command
{
    use Colors;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rosalana:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Rosalana package to the latest version';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (env('APP_ENV') === 'production') {
            $this->components->error('You cannot install packages in production');
            return 1;
        }

        $current = Package::version();

        $availableVersions = [];

        spin(function () use (&$availableVersions) {
            $availableVersions = Package::versions();
        }, 'Fetching available versions...');

        if (empty($availableVersions)) {
            $this->components->error('No available versions found');
            return 1;
        }

        $options = collect($availableVersions)
            ->map(function ($version) {
                return $version === 'dev-master' ? $this->red("Version dev (do not use in production)") : "Version {$version}.x.x";
            })
            ->toArray();
        
        $options = array_merge(['current' => $this->cyan("Keep current version ({$this->dim($current)})")], $options);

        dump($options);

        $major = select(
            label: 'Which ecosystem version would you like to update to?',
            options: $options,
            default: 'current',
        );
    }
}