<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;


class AddCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rosalana:core:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Rosalana Core package';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Installing Rosalana Core package...');

        $this->info('Publishing configuration...');

        // Configurations...
        $this->call('vendor:publish', [
            '--provider' => "Rosalana\Core\Providers\RosalanaCoreServiceProvider",
            '--tag' => "rosalana-config"
        ]);

        $this->info('Updating .env file...');

        // Environment...
        if (! file_exists(base_path('.env'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }

        // Write to .env
        file_put_contents(
            base_path('.env'),
            PHP_EOL .
                'JWT_SECRET=' . PHP_EOL .
                'ROSALANA_BASECAMP_URL=http://localhost:8000' . PHP_EOL .
                'ROSALANA_APP_SECRET=' . PHP_EOL,
            FILE_APPEND
        );

        $this->info('Installed Rosalana Core package');
    }
}
