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
        if ($current === "dev-master") {
            $this->info('Current version: ' . $this->red("Version dev (do not use in production)"));
        } else {
            $this->info('Current version: ' . $this->dim('v' . trim($current, '^') . '.x.x'));
        }

        $availableVersions = [];

        spin(function () use (&$availableVersions) {
            $availableVersions = Package::versions();
        }, 'Fetching available versions...');

        if (empty($availableVersions)) {
            $this->components->error('No available versions found');
            return 1;
        }

        $options = collect($availableVersions)
            ->mapWithKeys(function ($version) use ($current) {
                return [
                    $version === 'dev-master' ? 'dev-master' : "^$version" => 
                        (
                            $version === 'dev-master' 
                                ? $this->red("Version dev (do not use in production)") 
                                : "Version {$version}.x.x"
                        ) . (
                            $version === $current || "^$version" === $current 
                                ? $this->cyan(" (current)") 
                                : ''
                        )
                ];
            })
            ->toArray();

        // $options = array_merge(['current' => $this->cyan("Keep current version ({$this->dim($current)})")], $options);

        dump($options);
        dump($current);

        $major = select(
            label: 'Which ecosystem version would you like to update to?',
            options: $options,
            default: $current,
        );

        // $versionToUpdate = $major === 'current' ? null : "$major";

        spin(
            function () use ($major) {
                    $result = Package::switchVersion($major);
                    if ($result->failed()) {
                        $this->line("\n");
                        echo $this->red($result->errorOutput());
                        exit(1);
                    }
            },
            "Updating Rosalana ecosystem to version " . ($versionToUpdate ?? $current) . "..."
        );

        $this->components->success('Rosalana ecosystem updated successfully to version ' . ($versionToUpdate ?? $current));
    }
}
