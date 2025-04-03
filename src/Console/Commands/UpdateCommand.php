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