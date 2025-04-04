<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Laravel\Prompts\Concerns\Colors;
use Rosalana\Core\Services\Package;

class RosalanaCommnad extends Command
{
    use Colors;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rosalana';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rosalana command line interface';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $version = Package::version();

        $this->newLine();
        $this->info(' _____                    _                         ___ _    ___ ');
        $this->info('|     \ _ _   ____  __ _ | |  __ _ __ _   __ _     / __| |  |_ _|');
        $this->info('|  |  |  _  \/  __// _  || | / _  |  _ \ / _  |   | |  | |   | | ');
        $this->info('|    <  ( ) |\__ \| ( | || || ( | | | | | ( | |   | |__| |__ | | ');
        $this->info('|__|\_|\___//____/ \__,_||_| \__,_|_| |_|\__,_|    \___|____|___|');
        $this->newLine();

        $this->line("Version: {$this->cyan($version === 'dev-master' ? 'dev-master' : 'v' . trim($version, '^') . '.x.x')}");
        $this->newLine();
        $this->newLine();

        $commands = collect(Artisan::all())
            ->filter(fn($cmd, $name) => str_starts_with($name, 'rosalana:'))
            ->map(fn($cmd, $name) => [
                $name,
                $cmd->getDescription(),
            ])
            ->sortKeys();

        $this->table(['Command', 'Description'], $commands, 'compact');
    }
}