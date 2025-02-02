<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class InitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rosalana:init';

    /**
     * The console command description.
     */
    protected $description = 'Initialize Rosalana Development Environment';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Initializing Rosalana Development Environment...');

        if (! file_exists(base_path('.env'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }
        
        $this->components->info('Writing to .env file...');
        
        file_put_contents(
            base_path('.env'),
            PHP_EOL .
                'JWT_SECRET=' . PHP_EOL .
                'ROSALANA_BASECAMP_URL=http://localhost:8000' . PHP_EOL .
                'ROSALANA_APP_SECRET=' . PHP_EOL,
            FILE_APPEND
        );

        $this->info('Publishing configuration...');

        // Configurations...
        $this->call('vendor:publish', [
            '--provider' => "Rosalana\Core\Providers\RosalanaCoreServiceProvider",
            '--tag' => "rosalana-config"
        ]);

        // Install composer dependencies
        $this->components->info('Installing Rosalana Package Dependencies...');

        Process::fromShellCommandline('composer require rosalana/accounts')->run();
    }
}