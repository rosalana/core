<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Rosalana\Core\Services\Package;

use function Laravel\Prompts\select;

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

        $availableVersions = Package::versions();

        dd($availableVersions);

        $major = select(
            label: 'Which ecosystem version would you like to update to?',
            options: [
                'current' => "Keep current version ({$this->dim($current)})",
                'dev-master' => 'Latest development version (dev-master)',
            ],
            default: 'current',
        );
    }
}